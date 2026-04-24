<?php

namespace App\Jobs;

use App\Models\ChatAgent;
use App\Models\ProjectSetting;
use App\Models\Customer;
use App\Services\Agent\AgentContextService;
use App\Services\Agent\ConversationMemoryService;
use App\Services\AIService;
use App\Support\MetricsCollector;
use App\Support\ResilientHttp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Processes an AI reply asynchronously for Telegram and WhatsApp channels.
 *
 * Dispatched from webhook controllers so the HTTP response returns immediately (200 OK),
 * preventing webhook timeouts during slow OpenAI calls.
 */
class ProcessAiReply implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Retry up to 2 times (3 total attempts) with back-off.
     */
    public int $tries = 3;

    /**
     * @var int[] Back-off in seconds between retries.
     */
    public array $backoff = [10, 30];

    public function __construct(
        public readonly string $channel,
        public readonly string $chatId,
        public readonly string $combinedText,
        public readonly ?int $customerId,
    ) {}

    public function handle(): void
    {
        // For delayed-dispatch debounce: collect buffered messages.
        $text = $this->combinedText;
        if ($text === '') {
            $text = app(AIService::class)->collectBufferedMessages($this->chatId, $this->channel);
            if ($text === null || $text === '') {
                return;
            }
        }

        $requestStart = MetricsCollector::startTimer();

        $customer = null;
        $agentContext = [];

        try {
            $customer = $this->customerId !== null ? Customer::find($this->customerId) : null;
            if ($customer !== null) {
                $agentContext = app(AgentContextService::class)->buildContext($customer, $text);

                app(ConversationMemoryService::class)->addMessage(
                    $customer,
                    $this->channel,
                    'user',
                    $text,
                    ['chat_id' => $this->chatId]
                );
            }
        } catch (\Throwable $e) {
            Log::warning("{$this->channel} customer context persistence failed (job)", [
                'chat_id' => $this->chatId,
                'error' => $e->getMessage(),
            ]);
        }

        // If customer is escalated (waiting/human), save the message but don't reply.
        if ($customer !== null && $customer->mode !== 'bot') {
            Log::info("Skipping AI reply — customer mode is '{$customer->mode}'", [
                'customer_id' => $customer->id,
                'channel' => $this->channel,
                'chat_id' => $this->chatId,
            ]);
            return;
        }

        $this->sendTypingIndicator();

        $reply = app(AIService::class)->reply($text, $this->chatId, $this->channel, $agentContext);

        $this->stopTypingIndicator();

        // Detect escalation marker — strip it always; only act if escalation_enabled on agent.
        $shouldEscalate = str_contains($reply, '[ESCALATE]');
        $reply = trim(str_replace('[ESCALATE]', '', $reply));

        if ($shouldEscalate) {
            $agent = ChatAgent::getDefault();
            $escalationEnabled = $agent === null || ($agent->escalation_enabled ?? true);

            if ($escalationEnabled) {
                $silentHandoff      = $agent?->silent_handoff ?? false;
                $stopAiAfterHandoff = $agent?->stop_ai_after_handoff ?? true;

                if (!$silentHandoff) {
                    $reply = "Permintaan Anda sedang diteruskan ke agen kami. Mohon tunggu sebentar 🙏\nYour request is being forwarded to our agent. Please wait a moment 🙏";
                } else {
                    $reply = ''; // Silent — send nothing to customer
                }

                if ($customer !== null && $stopAiAfterHandoff) {
                    try {
                        $customer->update(['mode' => 'waiting']);
                        Log::info('Customer escalated to waiting queue by AI', [
                            'customer_id' => $customer->id,
                            'channel' => $this->channel,
                            'chat_id' => $this->chatId,
                        ]);
                    } catch (\Throwable $e) {
                        Log::error('Failed to set customer mode to waiting during escalation', [
                            'customer_id' => $customer->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                } else {
                    Log::info('Escalation triggered — stop_ai_after_handoff is off, customer mode unchanged', [
                        'customer_id' => $customer?->id,
                        'channel' => $this->channel,
                    ]);
                }
            } else {
                Log::info('Escalation marker detected but escalation_enabled is off — skipping', [
                    'customer_id' => $customer?->id,
                    'channel' => $this->channel,
                ]);
            }
        }

        if ($customer !== null) {
            try {
                app(ConversationMemoryService::class)->addMessage(
                    $customer,
                    $this->channel,
                    'assistant',
                    $reply,
                    ['chat_id' => $this->chatId]
                );
            } catch (\Throwable $e) {
                Log::warning("{$this->channel} assistant message persistence failed (job)", [
                    'chat_id' => $this->chatId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($reply !== '') {
            $this->sendReply($reply);
        }

        MetricsCollector::recordRequest($this->channel, MetricsCollector::elapsed($requestStart));
    }

    // ── Channel-specific send logic ──────────────────────────

    private function sendReply(string $text): void
    {
        match ($this->channel) {
            'telegram' => $this->sendTelegram($text),
            'whatsapp' => $this->sendWhatsApp($text),
            default => Log::error("ProcessAiReply: unsupported channel '{$this->channel}'"),
        };
    }

    private function sendTypingIndicator(): void
    {
        match ($this->channel) {
            'telegram' => $this->sendTelegramTyping(),
            'whatsapp' => $this->sendWhatsAppTyping(),
            default => null,
        };
    }

    private function stopTypingIndicator(): void
    {
        if ($this->channel === 'whatsapp') {
            $this->stopWhatsAppTyping();
        }
    }

    // ── Telegram ─────────────────────────────────────────────

    private function sendTelegram(string $text): void
    {
        $token = (string) ProjectSetting::getValue('telegram_bot_token', config('services.telegram.bot_token', ''));

        if ($token === '') {
            Log::error('TELEGRAM_BOT_TOKEN is not configured.');
            return;
        }

        ResilientHttp::post('telegram', "https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $this->chatId,
            'text' => $text,
        ], timeoutSeconds: 10);
    }

    private function sendTelegramTyping(): void
    {
        $token = (string) ProjectSetting::getValue('telegram_bot_token', config('services.telegram.bot_token', ''));

        if ($token === '') {
            return;
        }

        ResilientHttp::post('telegram', "https://api.telegram.org/bot{$token}/sendChatAction", [
            'chat_id' => $this->chatId,
            'action' => 'typing',
        ], timeoutSeconds: 10);
    }

    // ── WhatsApp (WAHA) ──────────────────────────────────────

    private function sendWhatsApp(string $text): void
    {
        $response = $this->postToWaha('/api/sendText', [
            'session' => $this->wahaSession(),
            'chatId' => $this->chatId,
            'text' => $text,
        ]);

        if ($response !== null && $response->failed()) {
            Log::error('Failed to send WAHA message (job)', [
                'chat_id' => $this->chatId,
                'status' => $response->status(),
                'response_size_bytes' => mb_strlen($response->body()),
            ]);
        }
    }

    private function sendWhatsAppTyping(): void
    {
        $response = $this->postToWaha('/api/startTyping', [
            'session' => $this->wahaSession(),
            'chatId' => $this->chatId,
        ]);

        if ($response !== null && $response->failed()) {
            Log::warning('Failed to start WAHA typing indicator (job)', [
                'chat_id' => $this->chatId,
                'status' => $response->status(),
            ]);
        }
    }

    private function stopWhatsAppTyping(): void
    {
        $response = $this->postToWaha('/api/stopTyping', [
            'session' => $this->wahaSession(),
            'chatId' => $this->chatId,
        ]);

        if ($response !== null && $response->failed()) {
            Log::warning('Failed to stop WAHA typing indicator (job)', [
                'chat_id' => $this->chatId,
                'status' => $response->status(),
            ]);
        }
    }

    private function postToWaha(string $endpoint, array $payload): ?\Illuminate\Http\Client\Response
    {
        $baseUrl = rtrim((string) ProjectSetting::getValue('whatsapp_base_url', config('services.whatsapp.base_url', '')), '/');

        if ($baseUrl === '') {
            Log::error('WAHA base URL is not configured.');
            return null;
        }

        $headers = ['Accept' => 'application/json'];
        $apiKey = (string) ProjectSetting::getValue('whatsapp_api_key', config('services.whatsapp.api_key', ''));

        if ($apiKey !== '') {
            $headers['X-Api-Key'] = $apiKey;
        }

        return ResilientHttp::post(
            service: 'waha',
            url: $baseUrl . $endpoint,
            payload: $payload,
            headers: $headers,
            timeoutSeconds: 10
        );
    }

    private function wahaSession(): string
    {
        return (string) ProjectSetting::getValue('whatsapp_session', config('services.whatsapp.session', 'default'));
    }
}

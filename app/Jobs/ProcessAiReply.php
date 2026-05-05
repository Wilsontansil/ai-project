<?php

namespace App\Jobs;

use App\Models\ChatAgent;
use App\Models\ProjectSetting;
use App\Models\Customer;
use App\Services\Agent\AgentContextService;
use App\Services\Agent\ConversationMemoryService;
use App\Services\AI\ConversationHistory;
use App\Services\AI\EscalationSummaryService;
use App\Services\AIService;
use App\Support\MetricsCollector;
use App\Support\ResilientHttp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use OpenAI;

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
     * Kill the job if it exceeds this many seconds.
     * Must be ≤ worker --timeout and < queue retry_after.
     */
    public int $timeout = 180;

    /**
     * @var int[] Back-off in seconds between retries.
     * Generous delays — OpenAI/tool latency can be 60-120 s.
     */
    public array $backoff = [60, 120];

    public function __construct(
        public readonly string $channel,
        public readonly string $chatId,
        public readonly string $combinedText,
        public readonly ?int $customerId,
        public readonly array $attachmentMeta = [],
    ) {}

    public function handle(): void
    {
        $aiService = app(AIService::class);

        // For delayed-dispatch debounce: collect buffered messages.
        $text = $this->combinedText;
        if ($text === '') {
            $text = $aiService->collectBufferedMessages($this->chatId, $this->channel);
            if ($text === null || $text === '') {
                return;
            }
        }

        // Prevent parallel replies for the same customer/channel.
        // Lock TTL matches $timeout so it auto-expires if the process is killed.
        if (!$aiService->acquireAiProcessingLock($this->chatId, $this->channel, 180)) {
            $aiService->bufferDebouncedMessage($this->chatId, $text, $this->channel);
            Log::info('ProcessAiReply skipped because another job is processing', [
                'channel' => $this->channel,
                'chat_id' => $this->chatId,
            ]);
            return;
        }

        try {
            $requestStart = MetricsCollector::startTimer();

            $customer = null;
            $agentContext = [];

            try {
                $customer = $this->customerId !== null ? Customer::find($this->customerId) : null;
                if ($customer !== null) {
                    $agentContext = app(AgentContextService::class)->buildContext($customer, $text);

                    $msgMeta = ['chat_id' => $this->chatId];
                    if (!empty($this->attachmentMeta)) {
                        $msgMeta['attachment'] = $this->attachmentMeta;
                    }
                    app(ConversationMemoryService::class)->addMessage(
                        $customer,
                        $this->channel,
                        'user',
                        $text,
                        $msgMeta
                    );
                }
            } catch (\Throwable $e) {
                Log::warning("{$this->channel} customer context persistence failed (job)", [
                    'chat_id' => $this->chatId,
                    'error' => $e->getMessage(),
                ]);
            }

            // If customer is escalated, block replies based on current handoff setting.
            if ($customer !== null && $this->shouldBlockAiReply($customer)) {
                Log::info("Skipping AI reply — customer mode is '{$customer->mode}'", [
                    'customer_id' => $customer->id,
                    'channel' => $this->channel,
                    'chat_id' => $this->chatId,
                ]);
                return;
            }

            $this->sendTypingIndicator();

            $reply = $aiService->reply($text, $this->chatId, $this->channel, $agentContext, $this->attachmentMeta);

            $this->stopTypingIndicator();

            // Detect escalation marker — strip it always; only act if agent has escalation_condition set.
            $shouldEscalate = str_contains($reply, '[ESCALATE]');
            $reply = trim(str_replace('[ESCALATE]', '', $reply));

            if ($shouldEscalate) {
                $agent = ChatAgent::getDefault();
                $silentHandoff      = $agent?->silent_handoff ?? false;
                $stopAiAfterHandoff = $agent?->stop_ai_after_handoff ?? true;

                if (!$silentHandoff) {
                    if ($stopAiAfterHandoff) {
                        $reply = "Permintaan Anda sedang diteruskan ke agen kami. Mohon tunggu sebentar 🙏\nYour request is being forwarded to our agent. Please wait a moment 🙏";
                    } elseif ($reply === '') {
                        $reply = "Permintaan Anda sedang diteruskan ke Human CS. Mohon tunggu sebentar 🙏";
                    }
                } else {
                    $reply = ''; // Silent — send nothing to customer
                }

                if ($customer !== null) {
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

                    // Generate escalation summary for backoffice agents.
                    app(EscalationSummaryService::class)->generate($customer, $this->chatId, $this->channel);
                }
            }

            if ($customer !== null && trim($reply) !== '') {
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
        } finally {
            $aiService->releaseAiProcessingLock($this->chatId, $this->channel);

            // Flush all buffered metric records accumulated during this job in one batch job.
            MetricsCollector::flush();

            // Drain one additional batch if messages arrived while AI was processing.
            if ($aiService->promoteBufferedMessagesToLeader($this->chatId, $this->channel)) {
                ProcessAiReply::dispatch($this->channel, $this->chatId, '', $this->customerId, [])
                    ->delay(now()->addSeconds($aiService->getMessageAwaitSeconds()));

                Log::info('Scheduled follow-up ProcessAiReply from buffered messages', [
                    'channel' => $this->channel,
                    'chat_id' => $this->chatId,
                ]);
            }
        }
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

    /** @deprecated Use App\Services\AI\EscalationSummaryService instead. */
    private function generateEscalationSummary(Customer $customer): void
    {
        try {
            $apiKey = (string) ProjectSetting::getValue('openai_api_key', config('services.openai.api_key', ''));
            if ($apiKey === '') {
                return;
            }

            // Use cache-based session history (current session only) instead of DB history
            // (all-day) so the summary reflects only the current escalation reason.
            $sessionMessages = app(ConversationHistory::class)->load($this->chatId, $this->channel);
            if (empty($sessionMessages)) {
                return;
            }

            $transcript = collect($sessionMessages)->map(function ($msg) {
                $role = $msg['role'] === 'assistant' ? 'Bot' : 'Customer';
                return "{$role}: " . ($msg['content'] ?? '');
            })->implode("\n");

            $client = OpenAI::client($apiKey);
            $agent  = ChatAgent::getDefault();
            $model  = $agent?->model ?? 'gpt-4.1-mini';

            $isReasoningModel = (bool) preg_match('/^o\d/', $model);
            $summarizePayload = [
                'model' => $model,
                'max_completion_tokens' => 80,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a summarizer. In 1-2 short sentences, summarize the customer\'s main issue from the conversation so a human agent can quickly understand why the customer was escalated. Be concise and factual. Reply in the same language as the conversation.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $transcript,
                    ],
                ],
            ];
            if (! $isReasoningModel) {
                $summarizePayload['temperature'] = 0.3;
            }

            $response = $client->chat()->create($summarizePayload);

            $summary = trim($response->choices[0]->message->content ?? '');

            if ($summary !== '') {
                $customer->update(['escalation_summary' => $summary]);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to generate escalation summary', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function shouldBlockAiReply(Customer $customer): bool
    {
        if ($customer->mode === 'human') {
            return true;
        }

        if ($customer->mode === 'waiting') {
            $agent = ChatAgent::getDefault();

            return $agent?->stop_ai_after_handoff ?? true;
        }

        return false;
    }
}

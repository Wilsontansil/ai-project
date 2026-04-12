<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\Agent\AgentContextService;
use App\Services\Agent\ConversationMemoryService;
use App\Services\Agent\CustomerIdentityService;
use App\Services\AIService;
use App\Models\Agent;
use App\Models\ProjectSetting;

class TelegramController extends Controller
{
    private string $telegramToken = '';
    private ?Agent $agent = null;
    private string $supportPhone = '';
    private string $supportUrl = '';

    public function __construct()
    {
        $this->telegramToken = (string) ProjectSetting::getValue('telegram_bot_token', config('services.telegram.bot_token', ''));
        $this->agent = Agent::getActive();
        $this->supportPhone = (string) ProjectSetting::getValue('support_phone', config('services.support.phone', '08120000000'));
        $this->supportUrl = (string) ProjectSetting::getValue('support_telegram_url', config('services.support.telegram_url', ''));
    }

    public function handleWebhook(Request $request)
    {
        $payload = $request->all();
        Log::info('Received Telegram webhook', ['payload' => $payload]);

        $text = $request->input('message.text');
        $chatId = $request->input('message.chat.id');

        if (!$text || !$chatId) {
            Log::warning('Invalid Telegram webhook payload', ['payload' => $request->all()]);
            return response()->json(['status' => 'ignored']);
        }

        $chatId = (string) $chatId;
        $combinedText = app(AIService::class)->collectDebouncedMessage($chatId, (string) $text);

        if ($combinedText === null) {
            return response()->json(['status' => 'queued']);
        }

        $customer = null;
        $agentContext = [];

        try {
            $customer = app(CustomerIdentityService::class)->resolve('telegram', $payload, $combinedText);
            $agentContext = app(AgentContextService::class)->buildContext($customer, $combinedText);

            app(ConversationMemoryService::class)->addMessage(
                $customer,
                'telegram',
                'user',
                $combinedText,
                ['chat_id' => $chatId]
            );
        } catch (\Throwable $e) {
            // Keep chat flow alive if DB/migration is temporarily unavailable.
            Log::warning('Telegram customer context persistence failed', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }

        $this->sendTyping($chatId);

        // Send channel so AI can include platform-specific handover info.
        $aiService = app(AIService::class);
        $rawReply = $aiService->reply($combinedText, $chatId, $this->agent, 'telegram', $agentContext);

        // Check if AI flagged this conversation for human escalation.
        if ($aiService->needsEscalation($rawReply)) {
            $aiService->createEscalation($customer, 'telegram', $chatId, $combinedText, $rawReply);
        }

        $reply = $this->appendHandoverContactIfNeeded($rawReply);

        if ($customer !== null) {
            try {
                app(ConversationMemoryService::class)->addMessage(
                    $customer,
                    'telegram',
                    'assistant',
                    $reply,
                    ['chat_id' => $chatId]
                );
            } catch (\Throwable $e) {
                Log::warning('Telegram assistant message persistence failed', [
                    'chat_id' => $chatId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->sendMessage($chatId, $reply);

        return response()->json(['status' => 'ok']);
    }

    private function sendMessage($chatId, $text)
    {
        if ($this->telegramToken === '') {
            Log::error('TELEGRAM_BOT_TOKEN is not configured.');
            return;
        }

        Http::post("https://api.telegram.org/bot" . $this->telegramToken . "/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text
        ]);
    }

    private function sendTyping($chatId)
    {
        if ($this->telegramToken === '') {
            return;
        }

        Http::post("https://api.telegram.org/bot" . $this->telegramToken . "/sendChatAction", [
            'chat_id' => $chatId,
            'action' => 'typing'
        ]);
    }

    private function appendHandoverContactIfNeeded(string $reply): string
    {
        $needsHandover = stripos($reply, 'human support') !== false
            || stripos($reply, 'transfer') !== false
            || stripos($reply, 'agent manusia') !== false;

        if (!$needsHandover) {
            return $reply;
        }

        $lines = ["\nKontak human support: {$this->supportPhone}"];

        if ($this->supportUrl !== '') {
            $lines[] = "Link Telegram support: {$this->supportUrl}";
        }

        return $reply . "\n" . implode("\n", $lines);
    }
}

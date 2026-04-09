<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\AIService;

class TelegramController extends Controller
{
    private string $telegramToken = '';
    private string $agent = 'PG';
    private string $supportPhone = '';
    private string $supportUrl = '';

    public function __construct()
    {
        $this->telegramToken = config('services.telegram.bot_token', '');
        $this->agent = 'PG'; // Could be dynamic based on chat or other factors
        $this->supportPhone = (string) config('services.support.phone', '08120000000');
        $this->supportUrl = (string) config('services.support.telegram_url', '');
    }

    public function handleWebhook(Request $request)
    {
        Log::info('Received Telegram webhook', ['payload' => $request->all()]);
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

        $this->sendTyping($chatId);

        // Send channel so AI can include platform-specific handover info.
        $reply = app(AIService::class)->reply($combinedText, $chatId, $this->agent, 'telegram');
        $reply = $this->appendHandoverContactIfNeeded($reply);

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

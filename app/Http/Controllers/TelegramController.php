<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\AIService;

class TelegramController extends Controller
{
    public function handleWebhook(Request $request)
    {
        Log::info('Received Telegram webhook', ['payload' => $request->all()]);
        $text = $request->input('message.text');
        $chatId = $request->input('message.chat.id');

        if (!$text || !$chatId) {
            Log::warning('Invalid Telegram webhook payload', ['payload' => $request->all()]);
            return response()->json(['status' => 'ignored']);
        }

        $this->sendTyping($chatId);

        // Send both message and chatId to keep service signature consistent.
        $reply = app(AIService::class)->reply($text, $chatId);

        $this->sendMessage($chatId, $reply);

        return response()->json(['status' => 'ok']);
    }

    private function sendMessage($chatId, $text)
    {
        $telegramtoken = '8460292911:AAEh1dcKps7elxi0ZjuX0z4jj2AOPwZcYgw';
        Http::post("https://api.telegram.org/bot" . $telegramtoken . "/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text
        ]);
    }

    private function sendTyping($chatId)
    {
        $telegramtoken = '8460292911:AAEh1dcKps7elxi0ZjuX0z4jj2AOPwZcYgw';
        Http::post("https://api.telegram.org/bot" . $telegramtoken . "/sendChatAction", [
            'chat_id' => $chatId,
            'action' => 'typing'
        ]);
    }
}

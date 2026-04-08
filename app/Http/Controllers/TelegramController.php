<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

        // 👉 TEMP: simple reply first
        $reply = "You said: " . $text;

        $this->sendMessage($chatId, $reply);

        return response()->json(['status' => 'ok']);
    }

    private function sendMessage($chatId, $text)
    {
        Http::post("https://api.telegram.org/bot" . env('TELEGRAM_BOT_TOKEN') . "/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text
        ]);
    }
}

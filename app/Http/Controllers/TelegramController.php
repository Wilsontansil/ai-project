<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TelegramController extends Controller
{
    public function handleWebhook(Request $request)
    {
        // Log the incoming request for debugging
        \Log::info('Received Telegram webhook:', $request->all());

        // Process the incoming message
        $message = $request->input('message');
        if ($message) {
            $chatId = $message['chat']['id'];
            $text = $message['text'] ?? '';

            // Here you can add your logic to handle the message and generate a response
            $responseText = "You said: " . $text;

            // Send a response back to Telegram
            $this->sendMessage($chatId, $responseText);
        }

        return response()->json(['status' => 'success']);
    }

    private function sendMessage($chatId, $text)
    {
        // $telegramToken = env('TELEGRAM_BOT_TOKEN');
        $telegramToken = '8460292911:AAEh1dcKps7elxi0ZjuX0z4jj2AOPwZcYgw';
        $url = "https://api.telegram.org/bot{$telegramToken}/sendMessage";

        $data = [
            'chat_id' => $chatId,
            'text' => $text,
        ];

        // Use Guzzle or any HTTP client to send the POST request to Telegram API
        try {
            \Http::post($url, $data);
        } catch (\Exception $e) {
            \Log::error('Error sending message to Telegram: ' . $e->getMessage());
        }
    }
}

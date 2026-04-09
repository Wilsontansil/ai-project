<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LiveChatController extends Controller
{
    public function handleWebhook(Request $request)
    {
        // Log the incoming request for debugging
        \Log::info('Received LiveChat webhook', ['payload' => $request->all()]);

        // Extract message and chat ID from the request
        $message = $request->input('message');
        $chatId = $request->input('chatId');

        if (!$message || !$chatId) {
            \Log::warning('Invalid LiveChat webhook payload', ['payload' => $request->all()]);
            return response()->json(['status' => 'ignored']);
        }

        // Here you would typically send the message to your AI service and get a reply
        // For demonstration, we'll just echo back the message
        $reply = "You said: " . $message;

        // Send the reply back to LiveChat (this is a placeholder - implement actual API call)
        \Log::info('Sending reply to LiveChat', ['chatId' => $chatId, 'reply' => $reply]);

        return response()->json(['status' => 'ok']);
    }
}

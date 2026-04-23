<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessAiReply;
use App\Services\Agent\CustomerIdentityService;
use App\Support\LogSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\AIService;

class TelegramController extends Controller
{
    public function handleWebhook(Request $request)
    {
        $text = $request->input('message.text');
        $chatId = $request->input('message.chat.id');

        if (!$text || !$chatId) {
            Log::warning('Invalid Telegram webhook payload', LogSanitizer::summarize($request->all()));
            return response()->json(['status' => 'ignored']);
        }

        $chatId = (string) $chatId;
        $isLeader = app(AIService::class)->bufferDebouncedMessage($chatId, (string) $text, 'telegram');

        if (!$isLeader) {
            return response()->json(['status' => 'queued']);
        }

        $customerId = null;
        try {
            $resolvedCustomer = app(CustomerIdentityService::class)->resolve('telegram', $request->all(), (string) $text);
            $customerId = $resolvedCustomer->id;
        } catch (\Throwable $e) {
            Log::warning('Failed to resolve Telegram customer before dispatch', ['chat_id' => $chatId, 'error' => $e->getMessage()]);
        }

        ProcessAiReply::dispatch('telegram', $chatId, '', $customerId)
            ->delay(now()->addSeconds(app(AIService::class)->getMessageAwaitSeconds()));

        return response()->json(['status' => 'ok']);
    }
}

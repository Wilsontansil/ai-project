<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessAiReply;
use App\Support\LogSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\AIService;
use App\Models\ProjectSetting;

class TelegramController extends Controller
{
    public function handleWebhook(Request $request)
    {
        // Log::info('Not Sanitized Telegram webhook received', ['body' => $request->all()]);
        // Log::info('Telegram webhook received', LogSanitizer::summarize($request->all()));

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

        ProcessAiReply::dispatch('telegram', $chatId, '', $request->all())
            ->delay(now()->addSeconds(2));

        return response()->json(['status' => 'ok']);
    }
}

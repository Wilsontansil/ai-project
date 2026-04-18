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
        $text = $request->input('message.text');
        $chatId = $request->input('message.chat.id');

        if (!$text || !$chatId) {
            Log::warning('Invalid Telegram webhook payload', LogSanitizer::summarize($request->all()));
            return response()->json(['status' => 'ignored']);
        }

        $chatId = (string) $chatId;
        $combinedText = app(AIService::class)->collectDebouncedMessage($chatId, (string) $text);

        if ($combinedText === null) {
            return response()->json(['status' => 'queued']);
        }

        ProcessAiReply::dispatch('telegram', $chatId, $combinedText, $request->all());

        return response()->json(['status' => 'ok']);
    }
}

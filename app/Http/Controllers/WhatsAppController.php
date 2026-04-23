<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessAiReply;
use App\Services\Agent\CustomerIdentityService;
use App\Support\LogSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\AIService;

class WhatsAppController extends Controller
{
    public function handleWebhook(Request $request)
    {
        $requestPayload = $request->all();
        // Log::info('Not Sanitized WAHA webhook received', ['body' => $requestPayload]);
        // Log::info('WhatsApp webhook received', LogSanitizer::summarize($requestPayload));

        $event = (string) ($request->input('event') ?? '');
        $payload = $request->input('payload', []);

        if ($event !== '' && stripos($event, 'message') === false) {
            return response()->json(['status' => 'ignored', 'reason' => 'unsupported_event']);
        }

        $fromMe = (bool) ($payload['fromMe'] ?? $request->input('fromMe', false));

        if ($fromMe) {
            return response()->json(['status' => 'ignored', 'reason' => 'outgoing_message']);
        }

        $text = $payload['body']
            ?? $payload['text']
            ?? ($payload['message']['body'] ?? null)
            ?? $request->input('body')
            ?? $request->input('text');

        $chatId = $payload['from']
            ?? $payload['chatId']
            ?? ($payload['chat']['id'] ?? null)
            ?? $request->input('from')
            ?? $request->input('chatId');

        if (!$text || !$chatId) {
            Log::warning('Invalid WAHA webhook payload', LogSanitizer::summarize($requestPayload));
            return response()->json(['status' => 'ignored', 'reason' => 'invalid_payload']);
        }

        $chatId = (string) $chatId;

        if ($this->isDuplicateMessage($request, $payload, $chatId, (string) $text)) {
            return response()->json(['status' => 'ignored', 'reason' => 'duplicate_message']);
        }

        $isLeader = app(AIService::class)->bufferDebouncedMessage($chatId, (string) $text, 'whatsapp');

        if (!$isLeader) {
            return response()->json(['status' => 'queued']);
        }

        $customerId = null;
        try {
            $resolvedCustomer = app(CustomerIdentityService::class)->resolve('whatsapp', $requestPayload, (string) $text);
            $customerId = $resolvedCustomer->id;
        } catch (\Throwable $e) {
            Log::warning('Failed to resolve WhatsApp customer before dispatch', ['chat_id' => $chatId, 'error' => $e->getMessage()]);
        }

        ProcessAiReply::dispatch('whatsapp', $chatId, '', $customerId)
            ->delay(now()->addSeconds(2));

        return response()->json(['status' => 'ok']);
    }

    private function isDuplicateMessage(Request $request, array $payload, string $chatId, string $text): bool
    {
        $messageId = (string) (
            $payload['id']
            ?? ($payload['message']['id'] ?? null)
            ?? $request->input('id')
            ?? ''
        );

        if ($messageId === '') {
            $messageId = sha1($chatId . '|' . trim($text));
        }

        $cacheKey = 'waha:processed:' . $messageId;
        $isNew = Cache::add($cacheKey, 1, now()->addMinutes(5));

        if (!$isNew) {
            Log::info('Duplicate WAHA message ignored', [
                'chat_id' => $chatId,
                'message_id' => $messageId,
            ]);
        }

        return !$isNew;
    }
}

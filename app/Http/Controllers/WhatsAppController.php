<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\AIService;

class WhatsAppController extends Controller
{
    private string $baseUrl = '';

    private string $session = '';

    private string $apiKey = '';

    private string $agent = 'PG';
    private string $supportPhone = '';
    private string $supportUrl = '';

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.whatsapp.base_url', ''), '/');
        $this->session = (string) config('services.whatsapp.session', 'default');
        $this->apiKey = (string) config('services.whatsapp.api_key', '');
        $this->agent = (string) config('services.whatsapp.agent', 'PG');
        $this->supportPhone = (string) config('services.support.phone', '08120000000');
        $this->supportUrl = (string) config('services.support.whatsapp_url', '');
    }

    public function handleWebhook(Request $request)
    {
        Log::info('Received WhatsApp webhook', ['payload' => $request->all()]);

        if ($request->isMethod('get')) {
            return response()->json(['status' => 'ok']);
        }

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
            Log::warning('Invalid WAHA webhook payload', ['payload' => $request->all()]);
            return response()->json(['status' => 'ignored', 'reason' => 'invalid_payload']);
        }

        $chatId = (string) $chatId;

        if ($this->isDuplicateMessage($request, $payload, $chatId, (string) $text)) {
            return response()->json(['status' => 'ignored', 'reason' => 'duplicate_message']);
        }

        $this->sendTyping($chatId);

        try {
            $reply = app(AIService::class)->reply((string) $text, $chatId, $this->agent, 'whatsapp');
        } finally {
            $this->stopTyping($chatId);
        }

        $reply = $this->appendHandoverContactIfNeeded($reply);

        $this->sendMessage($chatId, $reply);

        return response()->json(['status' => 'ok']);
    }

    private function sendMessage(string $chatId, string $text): void
    {
        if ($this->baseUrl === '') {
            Log::error('WAHA base URL is not configured.');
            return;
        }

        $response = $this->postToWaha('/api/sendText', [
            'session' => $this->session,
            'chatId' => $chatId,
            'text' => $text,
        ]);

        if ($response === null) {
            return;
        }

        if ($response->failed()) {
            Log::error('Failed to send WAHA message', [
                'chat_id' => $chatId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }
    }

    private function sendTyping(string $chatId): void
    {
        $response = $this->postToWaha('/api/startTyping', [
            'session' => $this->session,
            'chatId' => $chatId,
        ]);

        if ($response !== null && $response->failed()) {
            Log::warning('Failed to start WAHA typing indicator', [
                'chat_id' => $chatId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }
    }

    private function stopTyping(string $chatId): void
    {
        $response = $this->postToWaha('/api/stopTyping', [
            'session' => $this->session,
            'chatId' => $chatId,
        ]);

        if ($response !== null && $response->failed()) {
            Log::warning('Failed to stop WAHA typing indicator', [
                'chat_id' => $chatId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }
    }

    private function postToWaha(string $endpoint, array $payload)
    {
        if ($this->baseUrl === '') {
            Log::error('WAHA base URL is not configured.');
            return null;
        }

        $headers = ['Accept' => 'application/json'];

        if ($this->apiKey !== '') {
            $headers['X-Api-Key'] = $this->apiKey;
        }

        return Http::withHeaders($headers)->post($this->baseUrl . $endpoint, $payload);
    }

    private function isDuplicateMessage(Request $request, array $payload, string $chatId, string $text): bool
    {
        $messageId = (string) (
            $payload['id']
            ?? ($payload['message']['id'] ?? null)
            ?? $request->input('id')
            ?? ''
        );

        // Fallback hash if provider does not send message id.
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
            $lines[] = "Link WhatsApp support: {$this->supportUrl}";
        }

        return $reply . "\n" . implode("\n", $lines);
    }
}

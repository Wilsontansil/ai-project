<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\AIService;

class WhatsAppController extends Controller
{
    private string $baseUrl = '';

    private string $session = '';

    private string $apiKey = '';

    private string $agent = 'PG';

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.whatsapp.base_url', ''), '/');
        $this->session = (string) config('services.whatsapp.session', 'default');
        $this->apiKey = (string) config('services.whatsapp.api_key', '');
        $this->agent = (string) config('services.whatsapp.agent', 'PG');
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

        $reply = app(AIService::class)->reply((string) $text, (string) $chatId, $this->agent);

        $this->sendMessage((string) $chatId, $reply);

        return response()->json(['status' => 'ok']);
    }

    private function sendMessage(string $chatId, string $text): void
    {
        if ($this->baseUrl === '') {
            Log::error('WAHA base URL is not configured.');
            return;
        }

        $headers = ['Accept' => 'application/json'];

        if ($this->apiKey !== '') {
            $headers['X-Api-Key'] = $this->apiKey;
        }

        $response = Http::withHeaders($headers)->post($this->baseUrl . '/api/sendText', [
            'session' => $this->session,
            'chatId' => $chatId,
            'text' => $text,
        ]);

        if ($response->failed()) {
            Log::error('Failed to send WAHA message', [
                'chat_id' => $chatId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }
    }
}

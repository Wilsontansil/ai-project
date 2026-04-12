<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\Agent\AgentContextService;
use App\Services\Agent\ConversationMemoryService;
use App\Services\Agent\CustomerIdentityService;
use App\Services\AIService;
use App\Models\Agent;
use App\Models\ProjectSetting;

class WhatsAppController extends Controller
{
    private string $baseUrl = '';

    private string $session = '';

    private string $apiKey = '';

    private ?Agent $agent = null;
    private string $supportPhone = '';
    private string $supportUrl = '';

    public function __construct()
    {
        $this->baseUrl = rtrim((string) ProjectSetting::getValue('whatsapp_base_url', config('services.whatsapp.base_url', '')), '/');
        $this->session = (string) ProjectSetting::getValue('whatsapp_session', config('services.whatsapp.session', 'default'));
        $this->apiKey = (string) ProjectSetting::getValue('whatsapp_api_key', config('services.whatsapp.api_key', ''));
        $this->agent = Agent::getActive();
        $this->supportPhone = (string) ProjectSetting::getValue('support_phone', config('services.support.phone', '08120000000'));
        $this->supportUrl = (string) ProjectSetting::getValue('support_whatsapp_url', config('services.support.whatsapp_url', ''));
    }

    public function handleWebhook(Request $request)
    {
        $requestPayload = $request->all();
        Log::info('Received WhatsApp webhook', ['payload' => $requestPayload]);

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
            Log::warning('Invalid WAHA webhook payload', ['payload' => $requestPayload]);
            return response()->json(['status' => 'ignored', 'reason' => 'invalid_payload']);
        }

        $chatId = (string) $chatId;

        if ($this->isDuplicateMessage($request, $payload, $chatId, (string) $text)) {
            return response()->json(['status' => 'ignored', 'reason' => 'duplicate_message']);
        }

        $combinedText = app(AIService::class)->collectDebouncedMessage($chatId, (string) $text);

        if ($combinedText === null) {
            return response()->json(['status' => 'queued']);
        }

        $customer = null;
        $agentContext = [];

        try {
            $customer = app(CustomerIdentityService::class)->resolve('whatsapp', $requestPayload, $combinedText);
            $agentContext = app(AgentContextService::class)->buildContext($customer, $combinedText);

            app(ConversationMemoryService::class)->addMessage(
                $customer,
                'whatsapp',
                'user',
                $combinedText,
                ['chat_id' => $chatId]
            );
        } catch (\Throwable $e) {
            // Keep chat flow alive if DB/migration is temporarily unavailable.
            Log::warning('WhatsApp customer context persistence failed', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }

        $this->sendTyping($chatId);

        try {
            $aiService = app(AIService::class);
            $rawReply = $aiService->reply($combinedText, $chatId, $this->agent, 'whatsapp', $agentContext);
        } finally {
            $this->stopTyping($chatId);
        }

        // Check if AI flagged this conversation for human escalation.
        if ($aiService->needsEscalation($rawReply)) {
            $aiService->createEscalation($customer, 'whatsapp', $chatId, $combinedText, $rawReply);
        }

        $reply = $this->appendHandoverContactIfNeeded($rawReply);

        if ($customer !== null) {
            try {
                app(ConversationMemoryService::class)->addMessage(
                    $customer,
                    'whatsapp',
                    'assistant',
                    $reply,
                    ['chat_id' => $chatId]
                );
            } catch (\Throwable $e) {
                Log::warning('WhatsApp assistant message persistence failed', [
                    'chat_id' => $chatId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

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

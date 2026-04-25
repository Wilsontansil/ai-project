<?php

namespace App\Http\Controllers;

use App\Models\ProjectSetting;
use App\Services\Agent\ChatAttachmentStorageService;
use App\Support\LogSanitizer;
use App\Support\MetricsCollector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\Agent\AgentContextService;
use App\Services\Agent\ConversationMemoryService;
use App\Services\Agent\CustomerIdentityService;
use App\Services\AIService;

class LiveChatController extends Controller
{

    public function handleWebhook(Request $request)
    {
        $payload = $request->all();
        Log::info('LiveChat webhook received', LogSanitizer::summarize($payload));

        $response = null;

        $challenge = (string) $request->input('challenge', $request->query('challenge', ''));

        if ($challenge !== '') {
            $response = $this->buildChallengeResponse($request, $challenge);
        } else {
            $response = $this->buildAiResponse($request, $payload);
        }

        return $response;
    }

    private function buildChallengeResponse(Request $request, string $challenge)
    {
        $expectedToken = (string) ProjectSetting::getValue('livechat_verify_token', config('services.livechat.verify_token', ''));
        $providedToken = (string) $request->input('token', $request->query('token', ''));

        $isAuthorized = $expectedToken !== '' && $providedToken === $expectedToken;

        $response = response('', 401);

        if ($isAuthorized) {
            $response = response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        return $response;
    }

    private function buildAiResponse(Request $request, array $payload)
    {
        $text = $this->extractMessageText($request);
        $chatId = $this->extractChatId($payload, $request);

        $response = null;

        if ($chatId === null) {
            Log::warning('Invalid LiveChat webhook payload: missing chatId', LogSanitizer::summarize($payload));
            $response = response()->json([
                'status' => 'ignored',
                'reason' => 'invalid_payload',
            ]);
        } elseif ($text === null) {
            // Check for an attachment before falling back to the default ping response.
            [$attText, $attachmentMeta] = $this->extractAttachment($payload, $chatId ?? '');

            if ($attText !== null) {
                $text = $attText;
            } else {
                Log::info('LiveChat webhook has no message text, returning default response', ['chat_id' => $chatId]);
                $response = response()->json([
                    'responses' => [
                        [
                            'type'    => 'text',
                            'delay'   => 1000,
                            'message' => 'Halo! Ada yang bisa saya bantu?',
                        ],
                    ],
                ]);
            }
        }

        if ($chatId !== null && $text !== null) {
            $combinedText = app(AIService::class)->collectDebouncedMessage($chatId, $text, 'livechat');

            if ($combinedText === null) {
                $response = response()->json(['status' => 'queued']);
            } else {
                $reply = $this->generateAiReply($payload, $chatId, $combinedText, $attachmentMeta ?? []);
                $response = response()->json([
                    'responses' => [
                        [
                            'type' => 'text',
                            'delay' => 1000,
                            'message' => $reply,
                        ],
                    ],
                ]);
            }
        }

        return $response;
    }

    private function generateAiReply(array $payload, string $chatId, string $combinedText, array $attachmentMeta = []): string
    {
        $requestStart = MetricsCollector::startTimer();
        $customer = null;
        $agentContext = [];

        try {
            $customer = app(CustomerIdentityService::class)->resolve('livechat', $payload, $combinedText);
            $agentContext = app(AgentContextService::class)->buildContext($customer, $combinedText);

            // Always keep the latest active chat_id and thread_id for admin replies.
            $tags = $customer->tags ?? [];
            $threadId = (string) (data_get($payload, 'payload.thread_id') ?? '');
            $changed = ($tags['livechat_chat_id'] ?? null) !== $chatId
                || ($threadId !== '' && ($tags['livechat_thread_id'] ?? null) !== $threadId);
            if ($changed) {
                $tags['livechat_chat_id'] = $chatId;
                if ($threadId !== '') {
                    $tags['livechat_thread_id'] = $threadId;
                }
                $customer->update(['tags' => $tags]);
            }

            $msgMeta = ['chat_id' => $chatId];
            if (!empty($attachmentMeta)) {
                $msgMeta['attachment'] = $attachmentMeta;
            }
            app(ConversationMemoryService::class)->addMessage(
                $customer,
                'livechat',
                'user',
                $combinedText,
                $msgMeta
            );
        } catch (\Throwable $e) {
            Log::warning('LiveChat customer context persistence failed', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }

        // If customer is escalated (waiting/human), save the message but return empty.
        if ($customer !== null && $customer->mode !== 'bot') {
            Log::info("Skipping AI reply — customer mode is '{$customer->mode}'", [
                'customer_id' => $customer->id,
                'channel' => 'livechat',
                'chat_id' => $chatId,
            ]);
            return '';
        }

        $reply = app(AIService::class)->reply($combinedText, $chatId, 'livechat', $agentContext);

        MetricsCollector::recordRequest('livechat', MetricsCollector::elapsed($requestStart));

        if ($customer !== null) {
            try {
                app(ConversationMemoryService::class)->addMessage(
                    $customer,
                    'livechat',
                    'assistant',
                    $reply,
                    ['chat_id' => $chatId]
                );
            } catch (\Throwable $e) {
                Log::warning('LiveChat assistant message persistence failed', [
                    'chat_id' => $chatId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $reply;
    }

    private function extractMessageText(Request $request): ?string
    {
        $text = $request->input('message')
            ?? $request->input('text')
            ?? $request->input('payload.message')
            ?? $request->input('payload.text')
            ?? $request->input('event.message.text')
            ?? $request->input('data.message')
            ?? $request->input('data.text')
            ?? $request->input('chat.message')
            ?? $request->input('chat.text')
            ?? $request->input('attributes.message')
            ?? $request->input('attributes.user_message')
            ?? $request->input('attributes.visitor_message')
            ?? $request->input('userAttributes.message')
            ?? $request->input('userAttributes.user_message')
            ?? $request->input('userAttributes.visitor_message');

        $text = is_string($text) ? trim($text) : '';

        return $text !== '' ? $text : null;
    }

    private function extractChatId(array $payload, Request $request): ?string
    {
        $chatId = data_get($payload, 'chatId')
            ?? data_get($payload, 'userId')
            ?? data_get($payload, 'externalId')
            ?? data_get($payload, 'attributes.default_chat_id')
            ?? data_get($payload, 'attributes.default_conversation_id')
            ?? data_get($payload, 'chat_id')
            ?? data_get($payload, 'conversation_id')
            ?? data_get($payload, 'customer_id')
            ?? data_get($payload, 'user_id')
            ?? data_get($payload, 'payload.chatId')
            ?? data_get($payload, 'payload.chat_id')
            ?? data_get($payload, 'payload.userId')
            ?? data_get($payload, 'payload.externalId')
            ?? data_get($payload, 'payload.attributes.default_chat_id')
            ?? data_get($payload, 'payload.attributes.default_conversation_id')
            ?? data_get($payload, 'visitor.id')
            ?? data_get($payload, 'customer.id')
            ?? data_get($payload, 'chat.id')
            ?? data_get($payload, 'event.chat_id')
            ?? data_get($payload, 'event.customer_id')
            ?? $request->input('chat_id')
            ?? $request->input('conversation_id')
            ?? $request->input('customer_id')
            ?? $request->input('user_id')
            ?? $request->input('chatId')
            ?? $request->input('userId')
            ?? $request->input('externalId')
            ?? $request->input('payload.chatId')
            ?? $request->input('payload.userId')
            ?? $request->input('payload.externalId');

        $chatId = is_scalar($chatId) ? trim((string) $chatId) : '';

        return $chatId !== '' ? $chatId : null;
    }

    /**
     * Detect a file attachment in the LiveChat webhook payload, download it,
     * store it on SFTP, and return [syntheticText, attachmentMeta].
     *
     * @return array{0: string|null, 1: array}
     */
    private function extractAttachment(array $payload, string $chatId): array
    {
        $url = (string) (
            data_get($payload, 'payload.attachment.url')
            ?? data_get($payload, 'payload.file.url')
            ?? data_get($payload, 'payload.files.0.url')
            ?? data_get($payload, 'payload.url')
            ?? data_get($payload, 'attachment.url')
            ?? data_get($payload, 'file.url')
            ?? data_get($payload, 'url')
            ?? ''
        );

        if ($url === '') {
            return [null, []];
        }

        $filename = (string) (
            data_get($payload, 'payload.attachment.name')
            ?? data_get($payload, 'payload.file.name')
            ?? data_get($payload, 'payload.files.0.name')
            ?? data_get($payload, 'attachment.name')
            ?? data_get($payload, 'file.name')
            ?? basename(parse_url($url, PHP_URL_PATH) ?: 'file')
        );

        $mimeType = (string) (
            data_get($payload, 'payload.attachment.content_type')
            ?? data_get($payload, 'payload.file.content_type')
            ?? data_get($payload, 'attachment.content_type')
            ?? data_get($payload, 'file.content_type')
            ?? 'application/octet-stream'
        );

        $mediaType     = str_starts_with($mimeType, 'image/') ? 'image'
                       : (str_starts_with($mimeType, 'video/') ? 'video'
                       : (str_starts_with($mimeType, 'audio/') ? 'audio' : 'document'));
        $syntheticText = $mediaType === 'document'
            ? '[document: ' . $filename . ']'
            : '[' . $mediaType . ']';

        try {
            $download = Http::timeout(20)->get($url);

            if (!$download->successful()) {
                Log::warning('LiveChat attachment download failed', ['url' => $url, 'status' => $download->status()]);
                return [$syntheticText, []];
            }

            $meta = app(ChatAttachmentStorageService::class)->store(
                'livechat',
                $chatId,
                $filename,
                $mimeType,
                $download->body()
            );

            return [$syntheticText, $meta];
        } catch (\Throwable $e) {
            Log::error('LiveChat attachment download/store failed', [
                'chat_id' => $chatId,
                'error'   => $e->getMessage(),
            ]);

            return [$syntheticText, []];
        }
    }
}

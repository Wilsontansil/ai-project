<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessAiReply;
use App\Services\Agent\ChatAttachmentStorageService;
use App\Services\Agent\CustomerIdentityService;
use App\Support\LogSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\AIService;

class WhatsAppController extends Controller
{
    /** WAHA payload types that indicate a media message. */
    private const MEDIA_TYPES = ['image', 'document', 'video', 'audio', 'voice', 'ptt', 'sticker'];

    public function handleWebhook(Request $request)
    {
        $requestPayload = $request->all();

        $event   = (string) ($request->input('event') ?? '');
        $payload = $request->input('payload', []);

        if ($event !== '' && stripos($event, 'message') === false) {
            return response()->json(['status' => 'ignored', 'reason' => 'unsupported_event']);
        }

        $fromMe = (bool) ($payload['fromMe'] ?? $request->input('fromMe', false));

        if ($fromMe) {
            return response()->json(['status' => 'ignored', 'reason' => 'outgoing_message']);
        }

        $chatId = $payload['from']
            ?? $payload['chatId']
            ?? ($payload['chat']['id'] ?? null)
            ?? $request->input('from')
            ?? $request->input('chatId');

        // Text extraction (returns null for pure media messages).
        $text = $payload['body']
            ?? $payload['text']
            ?? ($payload['message']['body'] ?? null)
            ?? $request->input('body')
            ?? $request->input('text');
        // Treat empty string the same as null.
        if (is_string($text) && trim($text) === '') {
            $text = null;
        }

        $attachmentMeta = [];

        // If no text, check whether this is a media message.
        if ($text === null) {
            $mediaType = (string) ($payload['type'] ?? $payload['mediaType'] ?? '');

            if (in_array(strtolower($mediaType), self::MEDIA_TYPES, true)) {
                [$text, $attachmentMeta] = $this->extractMedia($payload, (string) ($chatId ?? ''));
            }
        }

        if (!$text || !$chatId) {
            Log::warning('Invalid WAHA webhook payload', LogSanitizer::summarize($requestPayload));
            return response()->json(['status' => 'ignored', 'reason' => 'invalid_payload']);
        }

        $chatId = (string) $chatId;
        $text   = (string) $text;

        if ($this->isDuplicateMessage($request, $payload, $chatId, $text)) {
            return response()->json(['status' => 'ignored', 'reason' => 'duplicate_message']);
        }

        $isLeader = app(AIService::class)->bufferDebouncedMessage($chatId, $text, 'whatsapp');

        if (!$isLeader) {
            return response()->json(['status' => 'queued']);
        }

        $customerId = null;
        try {
            $resolvedCustomer = app(CustomerIdentityService::class)->resolve('whatsapp', $requestPayload, $text);
            $customerId = $resolvedCustomer->id;
        } catch (\Throwable $e) {
            Log::warning('Failed to resolve WhatsApp customer before dispatch', ['chat_id' => $chatId, 'error' => $e->getMessage()]);
        }

        ProcessAiReply::dispatch('whatsapp', $chatId, '', $customerId, $attachmentMeta)
            ->delay(now()->addSeconds(app(AIService::class)->getMessageAwaitSeconds()));

        return response()->json(['status' => 'ok']);
    }

    /**
     * Download WAHA media and store on SFTP.
     *
     * @return array{0: string|null, 1: array}
     */
    private function extractMedia(array $payload, string $chatId): array
    {
        $mediaType = strtolower((string) ($payload['type'] ?? $payload['mediaType'] ?? 'document'));
        $mimeType  = (string) ($payload['mimetype'] ?? $payload['mime_type'] ?? 'application/octet-stream');
        $filename  = (string) ($payload['filename'] ?? $payload['name'] ?? '');
        $caption   = trim((string) ($payload['caption'] ?? ''));

        // Derive a sensible filename if none was provided.
        if ($filename === '') {
            $filename = match ($mediaType) {
                'image'           => 'photo.jpg',
                'video'           => 'video.mp4',
                'audio', 'voice', 'ptt' => 'audio.ogg',
                'sticker'         => 'sticker.webp',
                default           => 'document',
            };
        }

        $syntheticText = match ($mediaType) {
            'image'   => '[image]',
            'video'   => '[video]',
            'audio', 'voice', 'ptt' => '[audio]',
            'sticker' => '[sticker]',
            default   => '[document: ' . $filename . ']',
        };
        if ($caption !== '') {
            $syntheticText .= ' ' . $caption;
        }

        $contents = null;

        try {
            $mediaUrl = (string) ($payload['mediaUrl'] ?? $payload['media_url'] ?? '');
            $apiKey   = (string) config('services.whatsapp.api_key', '');
            $headers  = $apiKey !== '' ? ['X-Api-Key' => $apiKey] : [];

            if ($mediaUrl !== '') {
                $dl = Http::timeout(20)->withHeaders($headers)->get($mediaUrl);
                if ($dl->successful()) {
                    $contents = $dl->body();
                } else {
                    Log::warning('WAHA media download failed', ['url' => $mediaUrl, 'status' => $dl->status()]);
                }
            }

            // Fallback: body might be a data-URL (data:image/jpeg;base64,...).
            if ($contents === null) {
                $body = (string) ($payload['body'] ?? '');
                if (str_starts_with($body, 'data:')) {
                    $commaPos = strpos($body, ',');
                    if ($commaPos !== false) {
                        $decoded = base64_decode(substr($body, $commaPos + 1), true);
                        if ($decoded !== false) {
                            $contents = $decoded;
                        }
                    }
                }
            }

            if ($contents === null || $chatId === '') {
                return [$syntheticText, []];
            }

            $meta = app(ChatAttachmentStorageService::class)->store(
                'whatsapp',
                $chatId,
                $filename,
                $mimeType,
                $contents
            );

            return [$syntheticText, $meta];
        } catch (\Throwable $e) {
            Log::error('WAHA media download/store failed', [
                'chat_id' => $chatId,
                'error'   => $e->getMessage(),
            ]);

            return [$syntheticText, []];
        }
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
                'chat_id'    => $chatId,
                'message_id' => $messageId,
            ]);
        }

        return !$isNew;
    }
}


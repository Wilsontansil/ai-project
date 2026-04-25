<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessAiReply;
use App\Models\ProjectSetting;
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

        $chatId = $this->resolveChatId($request, $payload);

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
        $mediaType = $this->resolveMediaType($payload);
        $hasMediaSignal = $this->hasMediaSignal($payload);

        if ($mediaType === null && $hasMediaSignal) {
            $mediaType = $this->inferMediaTypeFromPayload($payload);
        }

        if ($hasMediaSignal) {
            Log::info('WAHA media diagnostics', [
                'event' => $event,
                'chat_id_present' => !empty($chatId),
                'media_type' => $mediaType,
                'mimetype' => (string) ($payload['mimetype'] ?? data_get($payload, 'message.mimetype') ?? ''),
                'has_media_url' => (string) ($payload['mediaUrl'] ?? $payload['media_url'] ?? data_get($payload, 'message.mediaUrl') ?? data_get($payload, 'message.url') ?? '') !== '',
                'has_data_url_body' => str_starts_with((string) ($payload['body'] ?? data_get($payload, 'message.body') ?? ''), 'data:'),
            ]);
        }

        // Process media even when text/caption is present, so attachment is still stored.
        if ($mediaType !== null) {
            [$mediaSyntheticText, $attachmentMeta] = $this->extractMedia($payload, (string) ($chatId ?? ''), $mediaType);

            if ($text === null || trim((string) $text) === '') {
                $text = $mediaSyntheticText;
            }

            if ($attachmentMeta === []) {
                Log::warning('WAHA media detected but attachment not stored', [
                    'chat_id' => (string) ($chatId ?? ''),
                    'media_type' => $mediaType,
                    'mimetype' => (string) ($payload['mimetype'] ?? data_get($payload, 'message.mimetype') ?? ''),
                ]);
            }
        } elseif ($hasMediaSignal) {
            Log::warning('WAHA media signal present but media type unresolved', [
                'chat_id' => (string) ($chatId ?? ''),
                'type' => (string) ($payload['type'] ?? data_get($payload, 'message.type') ?? ''),
                'media_type' => (string) ($payload['mediaType'] ?? data_get($payload, 'message.mediaType') ?? ''),
                'mimetype' => (string) ($payload['mimetype'] ?? data_get($payload, 'message.mimetype') ?? ''),
            ]);
        }

        if (!$text || !$chatId) {
            Log::warning('WAHA payload rejected by validator', [
                'has_text' => (bool) $text,
                'chat_id' => (string) ($chatId ?? ''),
                'media_type' => $mediaType,
                'has_media_signal' => $hasMediaSignal,
                'payload_keys' => array_keys($payload),
            ]);
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
    private function extractMedia(array $payload, string $chatId, string $mediaType): array
    {
        $mimeType  = (string) (
            $payload['mimetype']
            ?? $payload['mime_type']
            ?? data_get($payload, 'message.mimetype')
            ?? data_get($payload, 'message.mime_type')
            ?? 'application/octet-stream'
        );
        $filename  = (string) (
            $payload['filename']
            ?? $payload['name']
            ?? data_get($payload, 'message.filename')
            ?? data_get($payload, 'message.fileName')
            ?? ''
        );
        $caption   = trim((string) (
            $payload['caption']
            ?? data_get($payload, 'message.caption')
            ?? ''
        ));

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
            $mediaUrl = (string) (
                $payload['mediaUrl']
                ?? $payload['media_url']
                ?? $payload['url']
                ?? data_get($payload, 'message.mediaUrl')
                ?? data_get($payload, 'message.media_url')
                ?? data_get($payload, 'message.url')
                ?? data_get($payload, 'message.downloadUrl')
                ?? ''
            );
            $apiKey   = (string) ProjectSetting::getValue('whatsapp_api_key', config('services.whatsapp.api_key', ''));
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
                $body = (string) ($payload['body'] ?? data_get($payload, 'message.body') ?? '');
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
                Log::warning('WAHA media skipped: empty content or chat id', [
                    'chat_id' => $chatId,
                    'media_type' => $mediaType,
                    'has_media_url' => $mediaUrl !== '',
                    'has_data_url_body' => str_starts_with((string) ($payload['body'] ?? data_get($payload, 'message.body') ?? ''), 'data:'),
                ]);
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
            ?? data_get($payload, 'message.id._serialized')
            ?? data_get($payload, 'message.key.id')
            ?? $request->input('id')
            ?? ''
        );

        if ($messageId === '') {
            $mediaFingerprint = implode('|', array_filter([
                $this->resolveMediaType($payload) ?? '',
                (string) ($payload['mediaUrl'] ?? $payload['media_url'] ?? data_get($payload, 'message.mediaUrl') ?? data_get($payload, 'message.url') ?? ''),
                (string) ($payload['filename'] ?? $payload['name'] ?? data_get($payload, 'message.filename') ?? ''),
                (string) ($payload['timestamp'] ?? data_get($payload, 'messageTimestamp') ?? data_get($payload, 'message.timestamp') ?? ''),
            ]));

            $messageId = sha1($chatId . '|' . trim($text) . '|' . $mediaFingerprint);
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

    private function resolveMediaType(array $payload): ?string
    {
        $candidate = strtolower((string) (
            $payload['type']
            ?? $payload['mediaType']
            ?? data_get($payload, 'message.type')
            ?? data_get($payload, 'message.mediaType')
            ?? ''
        ));

        if (in_array($candidate, self::MEDIA_TYPES, true)) {
            return $candidate;
        }

        return null;
    }

    private function resolveChatId(Request $request, array $payload): ?string
    {
        $chatId = $payload['from']
            ?? $payload['chatId']
            ?? ($payload['chat']['id'] ?? null)
            ?? data_get($payload, 'key.remoteJid')
            ?? data_get($payload, 'message.key.remoteJid')
            ?? data_get($payload, 'sender.id')
            ?? data_get($payload, 'author')
            ?? data_get($payload, 'from.id')
            ?? $request->input('from')
            ?? $request->input('chatId')
            ?? $request->input('payload.key.remoteJid');

        if (!is_string($chatId)) {
            return null;
        }

        $chatId = trim($chatId);

        return $chatId !== '' ? $chatId : null;
    }

    private function hasMediaSignal(array $payload): bool
    {
        $mediaUrl = (string) (
            $payload['mediaUrl']
            ?? $payload['media_url']
            ?? $payload['url']
            ?? data_get($payload, 'message.mediaUrl')
            ?? data_get($payload, 'message.media_url')
            ?? data_get($payload, 'message.url')
            ?? data_get($payload, 'message.downloadUrl')
            ?? ''
        );

        $mimeType = (string) (
            $payload['mimetype']
            ?? $payload['mime_type']
            ?? data_get($payload, 'message.mimetype')
            ?? data_get($payload, 'message.mime_type')
            ?? ''
        );

        $body = (string) ($payload['body'] ?? data_get($payload, 'message.body') ?? '');

        return $mediaUrl !== '' || $mimeType !== '' || str_starts_with($body, 'data:');
    }

    private function inferMediaTypeFromPayload(array $payload): ?string
    {
        $mimeType = strtolower((string) (
            $payload['mimetype']
            ?? $payload['mime_type']
            ?? data_get($payload, 'message.mimetype')
            ?? data_get($payload, 'message.mime_type')
            ?? ''
        ));

        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }
        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }
        if (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        }
        if ($mimeType !== '') {
            return 'document';
        }

        $mediaUrl = strtolower((string) (
            $payload['mediaUrl']
            ?? $payload['media_url']
            ?? $payload['url']
            ?? data_get($payload, 'message.mediaUrl')
            ?? data_get($payload, 'message.media_url')
            ?? data_get($payload, 'message.url')
            ?? data_get($payload, 'message.downloadUrl')
            ?? ''
        ));

        if ($mediaUrl !== '') {
            if (str_contains($mediaUrl, '.jpg') || str_contains($mediaUrl, '.jpeg') || str_contains($mediaUrl, '.png') || str_contains($mediaUrl, '.webp') || str_contains($mediaUrl, '/image/')) {
                return 'image';
            }
            if (str_contains($mediaUrl, '.mp4') || str_contains($mediaUrl, '/video/')) {
                return 'video';
            }
            if (str_contains($mediaUrl, '.mp3') || str_contains($mediaUrl, '.ogg') || str_contains($mediaUrl, '/audio/')) {
                return 'audio';
            }

            return 'document';
        }

        $body = (string) ($payload['body'] ?? data_get($payload, 'message.body') ?? '');
        if (str_starts_with($body, 'data:image/')) {
            return 'image';
        }
        if (str_starts_with($body, 'data:video/')) {
            return 'video';
        }
        if (str_starts_with($body, 'data:audio/')) {
            return 'audio';
        }
        if (str_starts_with($body, 'data:')) {
            return 'document';
        }

        return null;
    }
}


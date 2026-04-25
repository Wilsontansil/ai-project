<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessAiReply;
use App\Services\Agent\ChatAttachmentStorageService;
use App\Services\Agent\CustomerIdentityService;
use App\Support\LogSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\AIService;

class TelegramController extends Controller
{
    public function handleWebhook(Request $request)
    {
        $message = $request->input('message', []);
        $chatId  = (string) ($message['chat']['id'] ?? '');

        if ($chatId === '') {
            Log::warning('Invalid Telegram webhook payload: missing chat_id', LogSanitizer::summarize($request->all()));
            return response()->json(['status' => 'ignored']);
        }

        // Try plain text first; fall back to media detection.
        $text           = isset($message['text']) && $message['text'] !== '' ? (string) $message['text'] : null;
        $attachmentMeta = [];

        if ($text === null) {
            [$text, $attachmentMeta] = $this->extractMedia($message, $chatId);
        }

        if ($text === null || $text === '') {
            Log::warning('Telegram webhook ignored: no text or supported media', LogSanitizer::summarize($request->all()));
            return response()->json(['status' => 'ignored']);
        }

        $isLeader = app(AIService::class)->bufferDebouncedMessage($chatId, $text, 'telegram');

        if (!$isLeader) {
            return response()->json(['status' => 'queued']);
        }

        $customerId = null;
        try {
            $resolvedCustomer = app(CustomerIdentityService::class)->resolve('telegram', $request->all(), $text);
            $customerId = $resolvedCustomer->id;
        } catch (\Throwable $e) {
            Log::warning('Failed to resolve Telegram customer before dispatch', ['chat_id' => $chatId, 'error' => $e->getMessage()]);
        }

        ProcessAiReply::dispatch('telegram', $chatId, '', $customerId, $attachmentMeta)
            ->delay(now()->addSeconds(app(AIService::class)->getMessageAwaitSeconds()));

        return response()->json(['status' => 'ok']);
    }

    /**
     * Detect Telegram media, download it from the Bot API, store on SFTP,
     * and return [syntheticText, attachmentMeta].
     *
     * @return array{0: string|null, 1: array}
     */
    private function extractMedia(array $message, string $chatId): array
    {
        $token = (string) config('services.telegram.bot_token', '');

        if ($token === '') {
            return [null, []];
        }

        $fileId    = null;
        $mimeType  = 'application/octet-stream';
        $filename  = 'file';
        $mediaType = 'document';

        if (!empty($message['photo'])) {
            $photo     = end($message['photo']);   // largest size
            $fileId    = $photo['file_id'] ?? null;
            $mimeType  = 'image/jpeg';
            $filename  = 'photo.jpg';
            $mediaType = 'image';
        } elseif (!empty($message['document'])) {
            $doc       = $message['document'];
            $fileId    = $doc['file_id'] ?? null;
            $mimeType  = $doc['mime_type'] ?? 'application/octet-stream';
            $filename  = $doc['file_name'] ?? 'document';
            $mediaType = 'document';
        } elseif (!empty($message['video'])) {
            $vid       = $message['video'];
            $fileId    = $vid['file_id'] ?? null;
            $mimeType  = $vid['mime_type'] ?? 'video/mp4';
            $filename  = 'video.mp4';
            $mediaType = 'video';
        } elseif (!empty($message['audio'])) {
            $aud       = $message['audio'];
            $fileId    = $aud['file_id'] ?? null;
            $mimeType  = $aud['mime_type'] ?? 'audio/mpeg';
            $filename  = $aud['file_name'] ?? 'audio.mp3';
            $mediaType = 'audio';
        } elseif (!empty($message['voice'])) {
            $voi       = $message['voice'];
            $fileId    = $voi['file_id'] ?? null;
            $mimeType  = $voi['mime_type'] ?? 'audio/ogg';
            $filename  = 'voice.ogg';
            $mediaType = 'audio';
        }

        if ($fileId === null) {
            return [null, []];
        }

        // Build synthetic text from media type + optional caption.
        $caption       = trim((string) ($message['caption'] ?? ''));
        $syntheticText = match ($mediaType) {
            'image'  => '[image]',
            'video'  => '[video]',
            'audio'  => '[audio]',
            default  => '[document: ' . $filename . ']',
        };
        if ($caption !== '') {
            $syntheticText .= ' ' . $caption;
        }

        try {
            // Resolve file path via Telegram Bot API.
            $getFile = Http::timeout(10)->get(
                "https://api.telegram.org/bot{$token}/getFile",
                ['file_id' => $fileId]
            );

            if (!$getFile->successful() || !$getFile->json('ok')) {
                Log::warning('Telegram getFile failed', ['file_id' => $fileId]);
                return [$syntheticText, []];
            }

            $filePath = (string) $getFile->json('result.file_path');

            // Download binary content.
            $download = Http::timeout(20)->get(
                "https://api.telegram.org/file/bot{$token}/{$filePath}"
            );

            if (!$download->successful()) {
                Log::warning('Telegram file download failed', ['file_path' => $filePath]);
                return [$syntheticText, []];
            }

            // Store on SFTP and return metadata.
            $meta = app(ChatAttachmentStorageService::class)->store(
                'telegram',
                $chatId,
                $filename,
                $mimeType,
                $download->body()
            );
            $meta['platform_file_id'] = $fileId;

            return [$syntheticText, $meta];
        } catch (\Throwable $e) {
            Log::error('Telegram media download/store failed', [
                'chat_id' => $chatId,
                'error'   => $e->getMessage(),
            ]);

            return [$syntheticText, []];
        }
    }
}

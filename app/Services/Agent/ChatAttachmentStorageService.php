<?php

namespace App\Services\Agent;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChatAttachmentStorageService
{
    /**
     * Upload raw binary content to the SFTP disk and return attachment metadata.
     *
     * @param  string  $platform      e.g. 'telegram', 'whatsapp', 'livechat'
     * @param  string  $externalId    customer platform_user_id / chat_id
     * @param  string  $originalName  original filename
     * @param  string  $mimeType      MIME type of the file
     * @param  string  $contents      raw binary content
     * @return array{disk:string, path:string, type:string, mime_type:string, original_name:string, size:int}
     */
    public function store(
        string $platform,
        string $externalId,
        string $originalName,
        string $mimeType,
        string $contents
    ): array {
        $year     = now()->format('Y');
        $month    = now()->format('m');
        $uuid     = (string) Str::uuid();
        $ext      = $this->guessExtension($originalName, $mimeType);
        $safeName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) ?: 'file';
        $safeId   = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $externalId);

        $path = "chat-attachments/{$platform}/{$safeId}/{$year}/{$month}/{$uuid}-{$safeName}.{$ext}";

        $ok = Storage::disk('sftp')->put($path, $contents);

        if ($ok === false) {
            throw new \RuntimeException("SFTP upload failed for path: {$path}");
        }

        return [
            'disk'          => 'sftp',
            'path'          => $path,
            'type'          => $this->resolveType($mimeType),
            'mime_type'     => $mimeType,
            'original_name' => $originalName,
            'size'          => strlen($contents),
        ];
    }

    private function resolveType(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }
        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }
        if (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        }

        return 'document';
    }

    private function guessExtension(string $filename, string $mimeType): string
    {
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if ($ext !== '') {
            return strtolower($ext);
        }

        $map = [
            'image/jpeg'      => 'jpg',
            'image/png'       => 'png',
            'image/gif'       => 'gif',
            'image/webp'      => 'webp',
            'video/mp4'       => 'mp4',
            'video/webm'      => 'webm',
            'audio/mpeg'      => 'mp3',
            'audio/ogg'       => 'ogg',
            'audio/wav'       => 'wav',
            'audio/mp4'       => 'm4a',
            'application/pdf' => 'pdf',
        ];

        return $map[$mimeType] ?? 'bin';
    }
}


<?php

namespace App\Services\AI;

/**
 * Formats outgoing assistant replies.
 *
 * Responsibilities:
 *   - Normalise whitespace and collapse consecutive blank lines.
 *   - Detect structured data requests that must keep their newline layout.
 *   - Truncate very long replies gracefully at sentence boundaries.
 *   - Convert inline verification lists to multiline format.
 *   - Guard against repeating the last assistant message verbatim.
 */
class ReplyFormatter
{
    public function format(string $reply): string
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $reply);
        $normalized = $this->formatInlineVerificationList($normalized);
        $lines = array_map(static fn ($line) => trim((string) $line), explode("\n", $normalized));

        $tidyLines = [];
        $lastBlank = false;

        foreach ($lines as $line) {
            $line = preg_replace('/[ \t]+/', ' ', $line) ?? $line;
            $isBlank = $line === '';

            if ($isBlank) {
                if (!$lastBlank) {
                    $tidyLines[] = '';
                }
                $lastBlank = true;
                continue;
            }

            $tidyLines[] = $line;
            $lastBlank = false;
        }

        $tidy = trim(implode("\n", $tidyLines));

        if ($this->isStructuredDataRequest($tidy)) {
            return $tidy;
        }

        // Keep reply complete; only hard-limit extremely long output.
        if (mb_strlen($tidy) <= 1400) {
            return $tidy;
        }

        $sentences = preg_split('/(?<=[.!?])\s+/', trim(preg_replace('/\s+/', ' ', $tidy) ?? $tidy)) ?: [];
        $sentences = array_values(array_filter(array_map('trim', $sentences), fn ($s) => $s !== ''));

        $chunks = [];
        $length = 0;

        foreach ($sentences as $sentence) {
            $segmentLength = mb_strlen($sentence) + ($length > 0 ? 1 : 0);

            if ($length + $segmentLength > 1400) {
                break;
            }

            $chunks[] = $sentence;
            $length += $segmentLength;
        }

        if ($chunks !== []) {
            return implode(' ', $chunks) . "\n\n(Pesan dipersingkat karena terlalu panjang.)";
        }

        return mb_substr($tidy, 0, 1400) . "\n\n(Pesan dipersingkat karena terlalu panjang.)";
    }

    /**
     * Format the reply and guard against verbatim repetition of the last assistant turn.
     *
     * @param array<int, array<string, string>> $history
     */
    public function prepare(array $history, string $reply): string
    {
        $formatted = $this->format($reply);

        if ($this->isRepeatedAssistantReply($history, $formatted)) {
            return 'Siap, saya lanjut dari data terbaru kamu ya.';
        }

        return $formatted;
    }

    /**
     * @param array<int, array<string, string>> $history
     */
    private function isRepeatedAssistantReply(array $history, string $reply): bool
    {
        for ($i = count($history) - 1; $i >= 0; $i--) {
            $item = $history[$i] ?? null;

            if (!is_array($item)) {
                continue;
            }

            if (($item['role'] ?? '') !== 'assistant') {
                continue;
            }

            $lastAssistant = trim((string) ($item['content'] ?? ''));

            return mb_strtolower($lastAssistant) === mb_strtolower(trim($reply));
        }

        return false;
    }

    private function formatInlineVerificationList(string $text): string
    {
        $patterns = [
            '/\s+(?=1\.\s*Username\s*:)/i',
            '/\s+(?=2\.\s*Nomor rekening\s*:)/i',
            '/\s+(?=3\.\s*Nama rekening\s*:)/i',
            '/\s+(?=4\.\s*Nama Bank\s*:)/i',
        ];

        foreach ($patterns as $pattern) {
            $text = preg_replace($pattern, "\n", $text) ?? $text;
        }

        return $text;
    }

    private function isStructuredDataRequest(string $text): bool
    {
        $markers = [
            'Username:',
            'Nama rekening:',
            'Nomor rekening:',
            'Nama Bank:',
            '1. Username:',
            '2. Nomor rekening:',
            '3. Nama rekening:',
            '4. Nama Bank:',
        ];

        $markerHits = 0;
        foreach ($markers as $marker) {
            if (stripos($text, $marker) !== false) {
                $markerHits++;
            }
        }

        if ($markerHits >= 2) {
            return true;
        }

        $fieldLineCount = 0;
        foreach (explode("\n", $text) as $line) {
            if (preg_match('/^[^:\n]{2,}:\s*$/', trim($line)) === 1) {
                $fieldLineCount++;
            }
        }

        return $fieldLineCount >= 3;
    }
}

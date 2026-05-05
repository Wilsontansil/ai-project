<?php

namespace App\Services\AI;

class EscalationIntentDetector
{
    /**
     * Keywords that indicate the customer asks for human takeover.
     * Keep all entries lowercase because matching is performed on normalized lowercase text.
     *
     * @var string[]
     */
    private const ESCALATION_KEYWORDS = [
        'human support',
        'agen manusia',
        'eskalasi',
        'escalation',
        'escalate',
        'oper ke manusia',
        'talk to human',
    ];

    /**
     * Negation cues that cancel an escalation request when they appear right before a keyword.
     *
     * @var string[]
     */
    private const NEGATION_CUES = [
        'jangan',
        'tidak',
        'ga',
        'gak',
        'nggak',
        'enggak',
        'tak',
        'dont',
        "don't",
        'no need',
        'ga usah',
        'gak usah',
        'nggak usah',
        'tidak usah',
    ];

    /**
     * Detect explicit escalation intent from user text.
     *
     * @return string|null Matched keyword when escalation is requested, otherwise null.
     */
    public function detect(string $text): ?string
    {
        $normalized = $this->normalize($text);
        if ($normalized === '') {
            return null;
        }

        foreach (self::ESCALATION_KEYWORDS as $keyword) {
            if (!str_contains($normalized, $keyword)) {
                continue;
            }

            if ($this->isNegated($normalized, $keyword)) {
                continue;
            }

            return $keyword;
        }

        return null;
    }

    private function normalize(string $text): string
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text) ?? '';

        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }

    private function isNegated(string $text, string $keyword): bool
    {
        $position = strpos($text, $keyword);
        if ($position === false) {
            return false;
        }

        $windowStart = max(0, $position - 24);
        $window = trim(substr($text, $windowStart, $position - $windowStart));

        foreach (self::NEGATION_CUES as $cue) {
            if ($window !== '' && str_contains($window, $cue)) {
                return true;
            }
        }

        return false;
    }
}


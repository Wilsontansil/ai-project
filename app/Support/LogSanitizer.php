<?php

namespace App\Support;

/**
 * Helpers to prevent PII / sensitive data from being written to log files.
 *
 * Usage:
 *   // Instead of logging a full webhook payload:
 *   Log::warning('Bad payload', LogSanitizer::summarize($payload));
 *
 *   // Instead of logging user-supplied tool arguments:
 *   Log::warning('Tool failed', ['args' => LogSanitizer::redactArguments($args)]);
 */
class LogSanitizer
{
    /**
     * Field names whose values must never appear in logs.
     * Matched case-insensitively against the last segment of a dot-notation key.
     */
    private const REDACTED_KEYS = [
        // Message content
        'text', 'body', 'message', 'content', 'caption',
        // Identity
        'first_name', 'last_name', 'name', 'full_name', 'username',
        'email', 'phone', 'phone_number', 'mobile', 'msisdn',
        // Chat identifiers (can be PII depending on jurisdiction)
        'from', 'chatid', 'chat_id', 'visitor_id', 'customer_id', 'user_id',
        // Credentials / tokens
        'password', 'token', 'secret', 'api_key', 'apikey', 'access_token',
        // LiveChat / generic visitor fields
        'visitor_message', 'user_message', 'chat_message', 'attributes',
    ];

    /**
     * Return safe metadata about a webhook payload — no values, only structural info.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function summarize(array $payload): array
    {
        return [
            'top_level_keys' => array_keys($payload),
            'size_bytes'     => strlen((string) json_encode($payload)),
            'event'          => self::safeScalar($payload['event'] ?? $payload['type'] ?? null),
        ];
    }

    /**
     * Return a copy of $args with PII field values replaced by [REDACTED].
     * Nested arrays are flattened to their key list rather than recursed into,
     * to avoid accidentally exposing nested user data.
     *
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public static function redactArguments(array $args): array
    {
        $result = [];

        foreach ($args as $key => $value) {
            $normalized = strtolower((string) $key);

            if (in_array($normalized, self::REDACTED_KEYS, true)) {
                $result[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                // Avoid logging nested structures that might contain PII;
                // record only the count of items instead.
                $result[$key] = '[array(' . count($value) . ')]';
            } elseif (is_string($value)) {
                // Cap string length so large blobs don't flood the log.
                $result[$key] = mb_substr($value, 0, 80);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Ensure a value is safe to write as a scalar log context entry.
     *
     * @param  mixed  $value
     */
    private static function safeScalar(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_scalar($value)) {
            return mb_substr((string) $value, 0, 80);
        }

        return null;
    }
}


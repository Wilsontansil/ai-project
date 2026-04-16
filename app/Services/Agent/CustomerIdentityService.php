<?php

namespace App\Services\Agent;

use App\Models\Customer;

class CustomerIdentityService
{
    public function resolve(string $platform, array $payload, ?string $message = null): Customer
    {
        $platform = strtolower(trim($platform));

        $platformUserId = $this->extractPlatformUserId($platform, $payload);
        $phoneNumber = $this->extractPhoneNumber($platform, $payload);
        $nameFromPayload = $this->extractName($platform, $payload);
        $nameFromMessage = $this->extractNameFromMessage((string) $message);

        $resolvedName = $this->pickBestName(null, $nameFromPayload, $nameFromMessage);

        $customer = Customer::query()->firstOrCreate(
            [
                'platform' => $platform,
                'platform_user_id' => $platformUserId,
            ],
            [
                'phone_number' => $phoneNumber,
                'name' => $resolvedName,
                'first_seen_at' => now(),
                'last_seen_at' => now(),
                'total_messages' => 0,
                'tags' => [],
            ]
        );

        $resolvedName = $this->pickBestName($customer->name, $nameFromPayload, $nameFromMessage);

        $customer->fill([
            'phone_number' => $customer->phone_number ?: $phoneNumber,
            'name' => $resolvedName,
            'last_seen_at' => now(),
            'total_messages' => $customer->total_messages + 1,
        ]);
        $customer->save();

        return $customer;
    }

    private function extractPlatformUserId(string $platform, array $payload): string
    {
        $platformUserId = null;

        if ($platform === 'telegram') {
            $platformUserId = (string) (
                data_get($payload, 'message.from.id')
                ?? data_get($payload, 'from.id')
                ?? data_get($payload, 'chat.id')
                ?? 'unknown_telegram_user'
            );
        }

        if ($platform === 'whatsapp') {
            $platformUserId = (string) (
                data_get($payload, 'payload.from')
                ?? data_get($payload, 'from')
                ?? data_get($payload, 'payload.chatId')
                ?? data_get($payload, 'chatId')
                ?? 'unknown_whatsapp_user'
            );
        }

        if ($platform === 'livechat') {
            $platformUserId = (string) (
                data_get($payload, 'chat_id')
                ?? data_get($payload, 'conversation_id')
                ?? data_get($payload, 'customer_id')
                ?? data_get($payload, 'user_id')
                ?? data_get($payload, 'visitor.id')
                ?? data_get($payload, 'customer.id')
                ?? data_get($payload, 'chat.id')
                ?? 'unknown_livechat_user'
            );
        }

        if ($platformUserId === null) {
            $platformUserId = (string) (
                data_get($payload, 'user_id')
                ?? data_get($payload, 'customer_id')
                ?? 'unknown_customer'
            );
        }

        return $platformUserId;
    }

    private function extractPhoneNumber(string $platform, array $payload): ?string
    {
        if ($platform === 'whatsapp') {
            $raw = (string) (
                data_get($payload, 'payload.SenderAlt')
                ?? data_get($payload, 'SenderAlt')
                ?? data_get($payload, 'payload.from')
                ?? data_get($payload, 'from')
                ?? ''
            );

            if ($raw === '') {
                return null;
            }

            // Strip @s.whatsapp.net / @c.us suffixes, keep only digits
            $clean = preg_replace('/@.*$/', '', $raw);
            $clean = preg_replace('/\D/', '', $clean);

            return $clean !== '' ? ('+' . $clean) : null;
        }

        return null;
    }

    private function extractName(string $platform, array $payload): ?string
    {
        $name = null;

        if ($platform === 'telegram') {
            $name = (string) (
                data_get($payload, 'message.from.first_name')
                ?? data_get($payload, 'message.from.username')
                ?? ''
            ) ?: null;
        }

        if ($platform === 'whatsapp') {
            $name = (string) (
                data_get($payload, 'payload.pushName')
                ?? data_get($payload, 'pushName')
                ?? ''
            ) ?: null;
        }

        if ($platform === 'livechat') {
            $name = (string) (
                data_get($payload, 'name')
                ?? data_get($payload, 'customer.name')
                ?? data_get($payload, 'visitor.name')
                ?? data_get($payload, 'chat.name')
                ?? ''
            ) ?: null;
        }

        if ($name === null) {
            $name = (string) (
                data_get($payload, 'name')
                ?? ''
            ) ?: null;
        }

        return $name;
    }

    /**
     * Learn possible name from free-form user message.
     */
    private function extractNameFromMessage(string $message): ?string
    {
        $text = trim(preg_replace('/\s+/', ' ', $message) ?? $message);

        if ($text === '') {
            return null;
        }

        $patterns = [
            '/(?:nama\s+saya|saya\s+bernama|aku\s+bernama|panggil\s+saya|my\s+name\s+is|i\s*am)\s+([\p{L}][\p{L}\s\'\.-]{1,40})/iu',
            '/(?:nama\s*:\s*)([\p{L}][\p{L}\s\'\.-]{1,40})/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches) !== 1) {
                continue;
            }

            $candidate = $this->sanitizeName((string) ($matches[1] ?? ''));

            if ($this->isLikelyPersonName($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function pickBestName(?string $currentName, ?string $payloadName, ?string $messageName): ?string
    {
        $current = $this->sanitizeName((string) $currentName);
        $fromPayload = $this->sanitizeName((string) $payloadName);
        $fromMessage = $this->sanitizeName((string) $messageName);

        if ($this->isLikelyPersonName($fromMessage) && $this->shouldReplaceName($current, $fromMessage)) {
            return $fromMessage;
        }

        if ($this->isLikelyPersonName($fromPayload) && $this->shouldReplaceName($current, $fromPayload)) {
            return $fromPayload;
        }

        return $this->isLikelyPersonName($current) ? $current : null;
    }

    private function sanitizeName(string $name): string
    {
        $name = trim($name);
        $name = preg_replace('/[\n\r\t]+/', ' ', $name) ?? $name;
        $name = preg_replace('/\s+/', ' ', $name) ?? $name;
        $name = trim($name, " \t\n\r\0\x0B.,!?;:-");

        return $name;
    }

    private function isLikelyPersonName(string $name): bool
    {
        $isValid = $name !== '';

        $isLengthValid = mb_strlen($name) >= 2 && mb_strlen($name) <= 40;
        $hasNumber = preg_match('/\d/', $name) === 1;

        $isValid = $isValid && $isLengthValid && !$hasNumber;

        $lower = mb_strtolower($name);
        $blocked = ['admin', 'customer service', 'cs ', 'help', 'tolong', 'reset', 'password', 'suspend'];

        $containsBlockedToken = false;
        foreach ($blocked as $token) {
            if (str_contains($lower, $token)) {
                $containsBlockedToken = true;
                break;
            }
        }

        $isValid = $isValid && !$containsBlockedToken;

        $wordCount = count(array_values(array_filter(explode(' ', $name), static fn ($part) => trim($part) !== '')));
        $isWordCountValid = $wordCount >= 1 && $wordCount <= 4;

        $isValid = $isValid && $isWordCountValid;

        return $isValid;
    }

    private function shouldReplaceName(string $currentName, string $candidateName): bool
    {
        $shouldReplace = $candidateName !== '';

        if ($shouldReplace && $currentName === '') {
            $shouldReplace = true;
        }

        if (mb_strtolower($currentName) === mb_strtolower($candidateName)) {
            $shouldReplace = false;
        }

        // Prefer better readable names (often message-provided) over weak aliases.
        $currentLooksAlias = preg_match('/[_\.\-]/', $currentName) === 1;
        $candidateLooksAlias = preg_match('/[_\.\-]/', $candidateName) === 1;

        if ($shouldReplace && $currentLooksAlias && !$candidateLooksAlias) {
            $shouldReplace = true;
        } elseif ($shouldReplace) {
            $shouldReplace = mb_strlen($candidateName) >= mb_strlen($currentName);
        }

        return $shouldReplace;
    }
}

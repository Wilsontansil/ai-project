<?php

namespace App\Services\Agent;

use App\Models\Customer;

class CustomerIdentityService
{
    public function resolve(string $platform, array $payload): Customer
    {
        $platform = strtolower(trim($platform));

        $platformUserId = $this->extractPlatformUserId($platform, $payload);
        $phoneNumber = $this->extractPhoneNumber($platform, $payload);
        $name = $this->extractName($platform, $payload);

        $customer = Customer::query()->firstOrCreate(
            [
                'platform' => $platform,
                'platform_user_id' => $platformUserId,
            ],
            [
                'phone_number' => $phoneNumber,
                'name' => $name,
                'first_seen_at' => now(),
                'last_seen_at' => now(),
                'total_messages' => 0,
                'tags' => [],
            ]
        );

        $customer->fill([
            'phone_number' => $customer->phone_number ?: $phoneNumber,
            'name' => $customer->name ?: $name,
            'last_seen_at' => now(),
            'total_messages' => $customer->total_messages + 1,
        ]);
        $customer->save();

        return $customer;
    }

    private function extractPlatformUserId(string $platform, array $payload): string
    {
        if ($platform === 'telegram') {
            return (string) (
                data_get($payload, 'message.from.id')
                ?? data_get($payload, 'from.id')
                ?? data_get($payload, 'chat.id')
                ?? 'unknown_telegram_user'
            );
        }

        if ($platform === 'whatsapp') {
            return (string) (
                data_get($payload, 'payload.from')
                ?? data_get($payload, 'from')
                ?? data_get($payload, 'payload.chatId')
                ?? data_get($payload, 'chatId')
                ?? 'unknown_whatsapp_user'
            );
        }

        return (string) (
            data_get($payload, 'user_id')
            ?? data_get($payload, 'customer_id')
            ?? 'unknown_customer'
        );
    }

    private function extractPhoneNumber(string $platform, array $payload): ?string
    {
        if ($platform === 'whatsapp') {
            $raw = (string) (
                data_get($payload, 'payload.from')
                ?? data_get($payload, 'from')
                ?? ''
            );

            if ($raw === '') {
                return null;
            }

            $clean = preg_replace('/[^0-9+]/', '', $raw);

            return $clean !== '' ? $clean : null;
        }

        return null;
    }

    private function extractName(string $platform, array $payload): ?string
    {
        if ($platform === 'telegram') {
            return (string) (
                data_get($payload, 'message.from.username')
                ?? data_get($payload, 'message.from.first_name')
                ?? ''
            ) ?: null;
        }

        if ($platform === 'whatsapp') {
            return (string) (
                data_get($payload, 'payload.pushName')
                ?? data_get($payload, 'pushName')
                ?? ''
            ) ?: null;
        }

        return (string) (
            data_get($payload, 'name')
            ?? ''
        ) ?: null;
    }
}

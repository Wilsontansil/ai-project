<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Cache;

/**
 * Manages per-chat conversation history in the cache.
 *
 * Each chat gets a rolling window of the last N turns (user + assistant pairs)
 * stored under a keyed cache entry with a configurable TTL.
 */
class ConversationHistory
{
    private int $maxMessages = 20;

    private int $ttlHours = 12;

    public function load(?string $chatId, string $channel = ''): array
    {
        if (!$chatId) {
            return [];
        }

        $history = Cache::get($this->key($chatId, $channel), []);

        return is_array($history) ? $history : [];
    }

    public function save(?string $chatId, array $history, string $userMessage, string $assistantReply, string $channel = ''): void
    {
        if (!$chatId) {
            return;
        }

        $history[] = ['role' => 'user', 'content' => $userMessage];
        $history[] = ['role' => 'assistant', 'content' => $assistantReply];

        // Keep only recent messages to control token usage.
        $history = array_slice($history, -$this->maxMessages);

        Cache::put($this->key($chatId, $channel), $history, now()->addHours($this->ttlHours));
    }

    public function key(string $chatId, string $channel = ''): string
    {
        $prefix = $channel !== '' ? $channel . ':' : '';

        return 'chat_context:' . $prefix . $chatId;
    }
}

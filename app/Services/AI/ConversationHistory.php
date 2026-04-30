<?php

namespace App\Services\AI;

use App\Models\ChatAgent;
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

    /** Cache TTL for conversation history. */
    private int $ttlHours = 2;

    /** If chat is idle longer than this, treat next message as a fresh session. */
    private int $inactivityMinutes = 60;

    public function load(?string $chatId, string $channel = '', ?ChatAgent $chatAgent = null): array
    {
        if (!$chatId) {
            return [];
        }

        // Reset context after a period of inactivity so old topics don't carry over.
        $lastActive = Cache::get($this->lastActiveKey($chatId, $channel));
        if ($lastActive !== null && (now()->timestamp - (int) $lastActive) > ($this->inactivityMinutes * 60)) {
            Cache::forget($this->key($chatId, $channel));
            Cache::forget($this->lastActiveKey($chatId, $channel));

            return [];
        }

        $history = Cache::get($this->key($chatId, $channel), []);

        if (!is_array($history)) {
            return [];
        }

        $maxMessages = $this->resolveMaxMessages($chatAgent);

        return array_slice($history, -$maxMessages);
    }

    public function save(?string $chatId, array $history, string $userMessage, string $assistantReply, string $channel = '', ?ChatAgent $chatAgent = null): void
    {
        if (!$chatId) {
            return;
        }

        $history[] = ['role' => 'user', 'content' => $userMessage];
        $history[] = ['role' => 'assistant', 'content' => $assistantReply];

        // Keep only recent messages to control token usage.
        $maxMessages = $this->resolveMaxMessages($chatAgent);
        $history = array_slice($history, -$maxMessages);

        $ttl = now()->addHours($this->ttlHours);
        Cache::put($this->key($chatId, $channel), $history, $ttl);
        Cache::put($this->lastActiveKey($chatId, $channel), now()->timestamp, $ttl);
    }

    public function clear(?string $chatId, string $channel = ''): void
    {
        if (!$chatId) {
            return;
        }

        Cache::forget($this->key($chatId, $channel));
        Cache::forget($this->lastActiveKey($chatId, $channel));
    }

    public function key(string $chatId, string $channel = ''): string
    {
        $prefix = $channel !== '' ? $channel . ':' : '';

        return 'chat_context:' . $prefix . $chatId;
    }

    private function lastActiveKey(string $chatId, string $channel = ''): string
    {
        $prefix = $channel !== '' ? $channel . ':' : '';

        return 'chat_last_active:' . $prefix . $chatId;
    }

    private function resolveMaxMessages(?ChatAgent $chatAgent): int
    {
        $value = (int) ($chatAgent?->max_history_messages ?? $this->maxMessages);

        return max(2, min($value, 100));
    }
}

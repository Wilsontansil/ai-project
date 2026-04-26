<?php

namespace App\Services\Agent;

use App\Models\Conversation;
use App\Models\Customer;
use Illuminate\Support\Collection;

class ConversationMemoryService
{
    public function addMessage(Customer $customer, string $channel, string $role, string $message, array $meta = []): Conversation
    {
        $today = now()->toDateString();

        $conversation = Conversation::query()->firstOrCreate(
            [
                'customer_id' => $customer->id,
                'conversation_date' => $today,
            ],
            [
                'channel' => $channel,
                'messages' => [],
            ]
        );

        $messages = $conversation->messages ?? [];
        $messages[] = [
            'role' => $role,
            'message' => $message,
            'meta' => $meta ?: null,
            'time' => now()->toTimeString(),
        ];

        // Cap stored messages to prevent unbounded JSON growth on long chats.
        $maxStoredMessages = 10000;
        $messages = array_slice($messages, -$maxStoredMessages);

        $conversation->update(['messages' => $messages, 'channel' => $channel]);

        return $conversation;
    }

    public function getRecent(Customer $customer, int $limit = 10): Collection
    {
        $conversation = Conversation::query()
            ->where('customer_id', $customer->id)
            ->latest('conversation_date')
            ->first();

        if (!$conversation) {
            return collect();
        }

        $messages = collect($conversation->messages ?? []);

        return $messages->slice(-$limit)->values();
    }

    public function toPromptSnippet(Customer $customer, int $limit = 10): string
    {
        $lines = [];

        foreach ($this->getRecent($customer, $limit) as $entry) {
            $lines[] = sprintf('%s: %s', $entry['role'], $entry['message']);
        }

        return implode("\n", $lines);
    }
}

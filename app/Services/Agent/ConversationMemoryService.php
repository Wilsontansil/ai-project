<?php

namespace App\Services\Agent;

use App\Models\Conversation;
use App\Models\Customer;
use Illuminate\Support\Collection;

class ConversationMemoryService
{
    public function addMessage(Customer $customer, string $channel, string $role, string $message, array $meta = []): Conversation
    {
        return Conversation::query()->create([
            'customer_id' => $customer->id,
            'channel' => $channel,
            'role' => $role,
            'message' => $message,
            'meta' => $meta,
        ]);
    }

    public function getRecent(Customer $customer, int $limit = 10): Collection
    {
        return Conversation::query()
            ->where('customer_id', $customer->id)
            ->latest('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    public function toPromptSnippet(Customer $customer, int $limit = 10): string
    {
        $lines = [];

        foreach ($this->getRecent($customer, $limit) as $row) {
            $lines[] = sprintf('%s: %s', $row->role, $row->message);
        }

        return implode("\n", $lines);
    }
}

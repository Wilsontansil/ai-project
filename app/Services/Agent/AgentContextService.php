<?php

namespace App\Services\Agent;

use App\Models\Customer;

class AgentContextService
{
    public function buildContext(Customer $customer, string $message): array
    {
        return [
            'customer_profile' => [
                'id' => $customer->id,
                'platform' => $customer->platform,
                'total_messages' => $customer->total_messages,
                'tags' => $customer->tags ?? [],
            ],
        ];
    }
}

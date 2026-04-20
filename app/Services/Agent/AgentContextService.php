<?php

namespace App\Services\Agent;

use App\Models\Customer;

class AgentContextService
{
    public function __construct(
        private readonly ConversationMemoryService $memoryService,
        private readonly BehaviorProfilerService $behaviorService,
    ) {
    }

    public function buildContext(Customer $customer, string $message): array
    {
        $behavior = $this->behaviorService->updateFromMessage($customer, $message);

        return [
            'customer_profile' => [
                'id' => $customer->id,
                'platform' => $customer->platform,
                'name' => $customer->name,
                'total_messages' => $customer->total_messages,
                'tags' => $customer->tags ?? [],
            ],
            'behavior' => [
                'intent' => $behavior->intent,
                'sentiment' => $behavior->sentiment,
                'frequency_score' => $behavior->frequency_score,
                'last_intent_at' => optional($behavior->last_intent_at)?->toDateTimeString(),
            ],
        ];
    }
}

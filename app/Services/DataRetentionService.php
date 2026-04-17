<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\CustomerBehavior;
use App\Models\ProjectSetting;
use Illuminate\Support\Facades\Log;

class DataRetentionService
{
    private const DEFAULT_CONVERSATION_RETENTION_DAYS = 90;

    private const DEFAULT_MEMORY_RETENTION_DAYS = 90;

    public function prune(?int $conversationDays = null, ?int $memoryDays = null, bool $dryRun = false): array
    {
        $conversationRetentionDays = $this->resolveConversationRetentionDays($conversationDays);
        $memoryRetentionDays = $this->resolveMemoryRetentionDays($memoryDays);

        $conversationCutoff = now()->subDays($conversationRetentionDays)->toDateString();
        $memoryCutoff = now()->subDays($memoryRetentionDays)->startOfDay();

        $conversationQuery = Conversation::query()
            ->where('conversation_date', '<', $conversationCutoff);

        $behaviorQuery = CustomerBehavior::query()
            ->whereNotNull('last_intent_at')
            ->where('last_intent_at', '<', $memoryCutoff);

        $conversationCount = (clone $conversationQuery)->count();
        $conversationCustomerCount = (clone $conversationQuery)
            ->distinct('customer_id')
            ->count('customer_id');

        $behaviorCount = (clone $behaviorQuery)->count();
        $behaviorCustomerCount = (clone $behaviorQuery)
            ->distinct('customer_id')
            ->count('customer_id');

        $deletedConversationCount = 0;
        $deletedBehaviorCount = 0;

        if (! $dryRun) {
            $deletedConversationCount = $conversationQuery->delete();
            $deletedBehaviorCount = $behaviorQuery->delete();
        }

        $summary = [
            'dry_run' => $dryRun,
            'conversation_retention_days' => $conversationRetentionDays,
            'memory_retention_days' => $memoryRetentionDays,
            'conversation_cutoff' => $conversationCutoff,
            'memory_cutoff' => $memoryCutoff->toDateTimeString(),
            'conversations_matched' => $conversationCount,
            'conversations_deleted' => $deletedConversationCount,
            'conversation_customers_affected' => $conversationCustomerCount,
            'memory_records_matched' => $behaviorCount,
            'memory_records_deleted' => $deletedBehaviorCount,
            'memory_customers_affected' => $behaviorCustomerCount,
        ];

        Log::info('Data retention prune completed', $summary);

        return $summary;
    }

    public function resolveConversationRetentionDays(?int $override = null): int
    {
        return $this->resolveRetentionDays(
            $override,
            'conversation_retention_days',
            config('services.retention.conversation_days', (string) self::DEFAULT_CONVERSATION_RETENTION_DAYS),
            self::DEFAULT_CONVERSATION_RETENTION_DAYS,
        );
    }

    public function resolveMemoryRetentionDays(?int $override = null): int
    {
        return $this->resolveRetentionDays(
            $override,
            'customer_memory_retention_days',
            config('services.retention.memory_days', (string) self::DEFAULT_MEMORY_RETENTION_DAYS),
            self::DEFAULT_MEMORY_RETENTION_DAYS,
        );
    }

    private function resolveRetentionDays(?int $override, string $settingKey, ?string $envFallback, int $default): int
    {
        $value = $override;

        if ($value === null) {
            $configuredValue = ProjectSetting::getValue($settingKey, $envFallback);
            $value = is_numeric($configuredValue) ? (int) $configuredValue : $default;
        }

        return max(1, $value);
    }
}

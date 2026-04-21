<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\ProjectSetting;
use Illuminate\Support\Facades\Log;

class DataRetentionService
{
    private const DEFAULT_CONVERSATION_RETENTION_DAYS = 90;

    public function prune(?int $conversationDays = null, bool $dryRun = false): array
    {
        $conversationRetentionDays = $this->resolveConversationRetentionDays($conversationDays);
        $conversationCutoff = now()->subDays($conversationRetentionDays)->toDateString();

        $conversationQuery = Conversation::query()
            ->where('conversation_date', '<', $conversationCutoff);

        $conversationCount = (clone $conversationQuery)->count();
        $conversationCustomerCount = (clone $conversationQuery)
            ->distinct('customer_id')
            ->count('customer_id');

        $deletedConversationCount = 0;

        if (! $dryRun) {
            $deletedConversationCount = $conversationQuery->delete();
        }

        $summary = [
            'dry_run' => $dryRun,
            'conversation_retention_days' => $conversationRetentionDays,
            'conversation_cutoff' => $conversationCutoff,
            'conversations_matched' => $conversationCount,
            'conversations_deleted' => $deletedConversationCount,
            'conversation_customers_affected' => $conversationCustomerCount,
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

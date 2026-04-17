<?php

namespace Tests\Feature\Console;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\CustomerBehavior;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataRetentionCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_retention_command_deletes_expired_conversations_and_memory(): void
    {
        $oldCustomer = Customer::query()->create([
            'platform' => 'telegram',
            'platform_user_id' => 'old-user',
            'phone_number' => '081111111111',
            'name' => 'Old User',
            'first_seen_at' => now()->subDays(120),
            'last_seen_at' => now()->subDays(120),
            'total_messages' => 3,
        ]);

        $recentCustomer = Customer::query()->create([
            'platform' => 'whatsapp',
            'platform_user_id' => 'recent-user',
            'phone_number' => '082222222222',
            'name' => 'Recent User',
            'first_seen_at' => now()->subDays(5),
            'last_seen_at' => now()->subDays(1),
            'total_messages' => 4,
        ]);

        $expiredConversation = Conversation::query()->create([
            'customer_id' => $oldCustomer->id,
            'channel' => 'telegram',
            'conversation_date' => now()->subDays(120)->toDateString(),
            'messages' => [['role' => 'user', 'message' => 'old message']],
        ]);

        $activeConversation = Conversation::query()->create([
            'customer_id' => $recentCustomer->id,
            'channel' => 'whatsapp',
            'conversation_date' => now()->subDays(10)->toDateString(),
            'messages' => [['role' => 'user', 'message' => 'recent message']],
        ]);

        $expiredMemory = CustomerBehavior::query()->create([
            'customer_id' => $oldCustomer->id,
            'intent' => 'general_question',
            'sentiment' => 'neutral',
            'frequency_score' => 2,
            'last_intent_at' => now()->subDays(120),
            'extra' => ['last_message_preview' => 'old preview'],
        ]);

        $activeMemory = CustomerBehavior::query()->create([
            'customer_id' => $recentCustomer->id,
            'intent' => 'deposit',
            'sentiment' => 'positive',
            'frequency_score' => 4,
            'last_intent_at' => now()->subDays(3),
            'extra' => ['last_message_preview' => 'recent preview'],
        ]);

        $this->artisan('retention:prune', [
            '--conversation-days' => 90,
            '--memory-days' => 90,
        ])
            ->expectsOutput('Data retention pruning complete.')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('conversations', ['id' => $expiredConversation->id]);
        $this->assertDatabaseHas('conversations', ['id' => $activeConversation->id]);
        $this->assertDatabaseMissing('customer_behaviors', ['id' => $expiredMemory->id]);
        $this->assertDatabaseHas('customer_behaviors', ['id' => $activeMemory->id]);
    }

    public function test_retention_command_dry_run_does_not_delete_data(): void
    {
        $customer = Customer::query()->create([
            'platform' => 'livechat',
            'platform_user_id' => 'dry-run-user',
            'phone_number' => null,
            'name' => 'Dry Run User',
            'first_seen_at' => now()->subDays(120),
            'last_seen_at' => now()->subDays(120),
            'total_messages' => 2,
        ]);

        $conversation = Conversation::query()->create([
            'customer_id' => $customer->id,
            'channel' => 'livechat',
            'conversation_date' => now()->subDays(120)->toDateString(),
            'messages' => [['role' => 'user', 'message' => 'dry run message']],
        ]);

        $memory = CustomerBehavior::query()->create([
            'customer_id' => $customer->id,
            'intent' => 'general_question',
            'sentiment' => 'neutral',
            'frequency_score' => 1,
            'last_intent_at' => now()->subDays(120),
            'extra' => ['last_message_preview' => 'dry run preview'],
        ]);

        $this->artisan('retention:prune', [
            '--conversation-days' => 90,
            '--memory-days' => 90,
            '--dry-run' => true,
        ])
            ->expectsOutput('Data retention pruning complete.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('conversations', ['id' => $conversation->id]);
        $this->assertDatabaseHas('customer_behaviors', ['id' => $memory->id]);
    }
}

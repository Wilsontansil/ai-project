<?php

namespace Tests\Feature\Webhooks;

use App\Jobs\ProcessAiReply;
use App\Services\AIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class TelegramWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const CHAT_ID = '123456';

    private const MESSAGE = 'Halo bot';

    public function test_telegram_webhook_dispatches_job(): void
    {
        Queue::fake();

        $aiService = Mockery::mock(AIService::class);
        $aiService->shouldReceive('bufferDebouncedMessage')
            ->once()
            ->with(self::CHAT_ID, self::MESSAGE, 'telegram')
            ->andReturn(true);

        $this->app->instance(AIService::class, $aiService);

        $response = $this->postJson('/api/telegram/webhook', [
            'message' => [
                'text' => self::MESSAGE,
                'chat' => ['id' => self::CHAT_ID],
            ],
        ]);

        $response->assertOk()->assertJson(['status' => 'ok']);

        Queue::assertPushed(ProcessAiReply::class, function (ProcessAiReply $job) {
            return $job->channel === 'telegram'
                && $job->chatId === self::CHAT_ID
                && $job->combinedText === '';
        });
    }

    public function test_telegram_webhook_ignores_invalid_payload(): void
    {
        $response = $this->postJson('/api/telegram/webhook', [
            'message' => [
                'chat' => ['id' => 123456],
            ],
        ]);

        $response->assertOk()->assertJson(['status' => 'ignored']);
    }
}

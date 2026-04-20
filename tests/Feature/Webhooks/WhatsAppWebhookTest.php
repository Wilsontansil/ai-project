<?php

namespace Tests\Feature\Webhooks;

use App\Jobs\ProcessAiReply;
use App\Services\AIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class WhatsAppWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const CHAT_ID = '628111111111@c.us';

    private const MESSAGE = 'cek bonus';

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_whatsapp_webhook_dispatches_job(): void
    {
        Queue::fake();

        $aiService = Mockery::mock(AIService::class);
        $aiService->shouldReceive('bufferDebouncedMessage')
            ->once()
            ->with(self::CHAT_ID, self::MESSAGE, 'whatsapp')
            ->andReturn(true);

        $this->app->instance(AIService::class, $aiService);

        $response = $this->postJson('/api/whatsapp/webhook', [
            'event' => 'message',
            'payload' => [
                'id' => 'msg-1',
                'fromMe' => false,
                'from' => self::CHAT_ID,
                'body' => self::MESSAGE,
            ],
        ]);

        $response->assertOk()->assertJson(['status' => 'ok']);

        Queue::assertPushed(ProcessAiReply::class, function (ProcessAiReply $job) {
            return $job->channel === 'whatsapp'
                && $job->chatId === self::CHAT_ID
                && $job->combinedText === '';
        });
    }

    public function test_whatsapp_webhook_returns_health_response_on_get(): void
    {
        $this->getJson('/api/whatsapp/webhook')
            ->assertOk()
            ->assertJson(['status' => 'ok']);
    }
}

<?php

namespace Tests\Feature\Webhooks;

use App\Services\AIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class TelegramWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const CHAT_ID = '123456';

    private const MESSAGE = 'Halo bot';

    private const AI_REPLY = 'Halo juga!';

    public function test_telegram_webhook_processes_message_and_sends_reply(): void
    {
        config(['services.telegram.bot_token' => 'telegram-test-token']);

        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $aiService = Mockery::mock(AIService::class);
        $aiService->shouldReceive('collectDebouncedMessage')
            ->once()
            ->with(self::CHAT_ID, self::MESSAGE)
            ->andReturn(self::MESSAGE);
        $aiService->shouldReceive('reply')
            ->once()
            ->with(self::MESSAGE, self::CHAT_ID, 'telegram', Mockery::type('array'))
            ->andReturn(self::AI_REPLY);

        $this->app->instance(AIService::class, $aiService);

        $response = $this->postJson('/api/telegram/webhook', [
            'message' => [
                'text' => self::MESSAGE,
                'chat' => ['id' => self::CHAT_ID],
            ],
        ]);

        $response->assertOk()->assertJson(['status' => 'ok']);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/sendChatAction')
            && (string) $request['chat_id'] === self::CHAT_ID
            && (string) $request['action'] === 'typing');

        Http::assertSent(fn ($request) => str_contains($request->url(), '/sendMessage')
            && (string) $request['chat_id'] === self::CHAT_ID
            && (string) $request['text'] === self::AI_REPLY);
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

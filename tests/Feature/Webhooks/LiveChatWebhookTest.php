<?php

namespace Tests\Feature\Webhooks;

use App\Models\ProjectSetting;
use App\Services\AIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class LiveChatWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const CHAT_ID = 'livechat-1';

    private const MESSAGE = 'halo livechat';

    private const AI_REPLY = 'halo dari ai';

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        ProjectSetting::clearCache();
    }

    public function test_livechat_challenge_requires_valid_token_when_configured(): void
    {
        ProjectSetting::query()->create([
            'key' => 'livechat_verify_token',
            'value' => 'verify-token-123',
            'label' => 'Verify Token',
            'group' => 'livechat',
            'type' => 'secret',
        ]);

        ProjectSetting::clearCache();

        $this->get('/api/livechat/webhook?challenge=abc123&token=wrong-token')
            ->assertStatus(401);

        $this->get('/api/livechat/webhook?challenge=abc123&token=verify-token-123')
            ->assertOk()
            ->assertSee('abc123');
    }

    public function test_livechat_webhook_returns_ai_message_response(): void
    {
        $aiService = Mockery::mock(AIService::class);
        $aiService->shouldReceive('collectDebouncedMessage')
            ->once()
            ->with(self::CHAT_ID, self::MESSAGE)
            ->andReturn(self::MESSAGE);
        $aiService->shouldReceive('reply')
            ->once()
            ->with(self::MESSAGE, self::CHAT_ID, 'livechat', Mockery::type('array'))
            ->andReturn(self::AI_REPLY);

        $this->app->instance(AIService::class, $aiService);

        $response = $this->postJson('/api/livechat/webhook', [
            'chatId' => self::CHAT_ID,
            'message' => self::MESSAGE,
        ]);

        $response->assertOk()
            ->assertJsonPath('responses.0.type', 'text')
            ->assertJsonPath('responses.0.message', self::AI_REPLY);
    }
}

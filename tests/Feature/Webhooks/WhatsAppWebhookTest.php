<?php

namespace Tests\Feature\Webhooks;

use App\Models\ProjectSetting;
use App\Services\AIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class WhatsAppWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const CHAT_ID = '628111111111@c.us';

    private const MESSAGE = 'cek bonus';

    private const AI_REPLY = 'bonus tersedia';

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        ProjectSetting::clearCache();
    }

    public function test_whatsapp_webhook_processes_message_and_calls_waha_endpoints(): void
    {
        ProjectSetting::query()->create([
            'key' => 'whatsapp_base_url',
            'value' => 'https://waha.test',
            'label' => 'Base URL',
            'group' => 'whatsapp',
            'type' => 'url',
        ]);

        ProjectSetting::query()->create([
            'key' => 'whatsapp_session',
            'value' => 'default',
            'label' => 'Session',
            'group' => 'whatsapp',
            'type' => 'text',
        ]);

        ProjectSetting::query()->create([
            'key' => 'whatsapp_api_key',
            'value' => 'test-api-key',
            'label' => 'API Key',
            'group' => 'whatsapp',
            'type' => 'secret',
        ]);

        ProjectSetting::clearCache();

        config([
            'services.whatsapp.base_url' => '',
            'services.whatsapp.session' => 'default',
            'services.whatsapp.api_key' => '',
        ]);

        Http::fake([
            'https://waha.test/*' => Http::response(['ok' => true], 200),
        ]);

        $aiService = Mockery::mock(AIService::class);
        $aiService->shouldReceive('collectDebouncedMessage')
            ->once()
            ->with(self::CHAT_ID, self::MESSAGE)
            ->andReturn(self::MESSAGE);
        $aiService->shouldReceive('reply')
            ->once()
            ->with(self::MESSAGE, self::CHAT_ID, 'whatsapp', Mockery::type('array'))
            ->andReturn(self::AI_REPLY);

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

        Http::assertSent(fn ($request) => $request->url() === 'https://waha.test/api/startTyping'
            && (string) $request['chatId'] === self::CHAT_ID
            && (string) $request['session'] === 'default');

        Http::assertSent(fn ($request) => $request->url() === 'https://waha.test/api/stopTyping'
            && (string) $request['chatId'] === self::CHAT_ID
            && (string) $request['session'] === 'default');

        Http::assertSent(fn ($request) => $request->url() === 'https://waha.test/api/sendText'
            && (string) $request['chatId'] === self::CHAT_ID
            && (string) $request['text'] === self::AI_REPLY);
    }

    public function test_whatsapp_webhook_returns_health_response_on_get(): void
    {
        $this->getJson('/api/whatsapp/webhook')
            ->assertOk()
            ->assertJson(['status' => 'ok']);
    }
}

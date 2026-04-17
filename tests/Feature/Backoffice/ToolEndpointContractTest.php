<?php

namespace Tests\Feature\Backoffice;

use App\Models\ProjectSetting;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ToolEndpointContractTest extends TestCase
{
    use RefreshDatabase;

    private const ROUTE = '/game-gacor';

    private const BASE_URL = 'https://endpoint.test';

    private const FULL_URL = 'https://endpoint.test/game-gacor';

    private const USERNAME = 'player1';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        Cache::flush();
        ProjectSetting::clearCache();
    }

    public function test_test_endpoint_returns_validation_error_when_base_url_not_configured(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('backoffice.tools.testEndpoint'), [
                'route' => self::ROUTE,
                'body' => ['username' => self::USERNAME],
            ])
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => 'Webhook base URL belum dikonfigurasi di Settings.',
            ]);
    }

    public function test_test_endpoint_forwards_payload_and_returns_upstream_contract(): void
    {
        $user = User::factory()->create();

        ProjectSetting::query()->create([
            'key' => 'webhook_base_url',
            'value' => self::BASE_URL,
            'label' => 'Base URL',
            'group' => 'webhook',
            'type' => 'url',
        ]);

        Http::fake([
            self::BASE_URL . '/*' => Http::response([
                'result' => 'ok',
                'items' => ['slot-a', 'slot-b'],
            ], 200),
        ]);

        $this->actingAs($user)
            ->postJson(route('backoffice.tools.testEndpoint'), [
                'route' => self::ROUTE,
                'body' => ['username' => self::USERNAME],
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'status' => 200,
                'url' => self::FULL_URL,
                'body_sent' => ['username' => self::USERNAME],
            ])
            ->assertJsonPath('response.result', 'ok')
            ->assertJsonPath('response.items.0', 'slot-a');

        Http::assertSent(fn ($request) => $request->url() === self::FULL_URL
            && (string) $request['username'] === self::USERNAME);
    }

    public function test_test_endpoint_surfaces_upstream_error_status(): void
    {
        $user = User::factory()->create();

        ProjectSetting::query()->create([
            'key' => 'webhook_base_url',
            'value' => self::BASE_URL,
            'label' => 'Base URL',
            'group' => 'webhook',
            'type' => 'url',
        ]);

        Http::fake([
            self::BASE_URL . '/*' => Http::response(['error' => 'upstream failed'], 500),
        ]);

        $this->actingAs($user)
            ->postJson(route('backoffice.tools.testEndpoint'), [
                'route' => self::ROUTE,
                'body' => ['username' => self::USERNAME],
            ])
            ->assertOk()
            ->assertJson([
                'success' => false,
                'status' => 500,
                'url' => self::FULL_URL,
            ])
            ->assertJsonPath('response.error', 'upstream failed');
    }
}


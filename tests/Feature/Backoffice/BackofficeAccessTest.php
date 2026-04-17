<?php

namespace Tests\Feature\Backoffice;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackofficeAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function test_guest_cannot_access_backoffice_dashboard(): void
    {
        $this->get('/backoffice')
            ->assertRedirect('/backoffice/login');
    }

    public function test_guest_cannot_execute_tool_endpoint_test_route(): void
    {
        $this->post('/backoffice/tools/test-endpoint', [
            'route' => '/any-route',
            'body' => ['k' => 'v'],
        ])->assertRedirect('/backoffice/login');
    }

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('backoffice.dashboard'))
            ->assertOk();
    }
}


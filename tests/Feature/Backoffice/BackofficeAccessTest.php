<?php

namespace Tests\Feature\Backoffice;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackofficeAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_backoffice_dashboard(): void
    {
        $this->get(route('backoffice.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_guest_cannot_execute_tool_endpoint_test_route(): void
    {
        $this->postJson(route('backoffice.tools.testEndpoint'), [
            'route' => '/any-route',
            'body' => ['k' => 'v'],
        ])->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('backoffice.dashboard'))
            ->assertOk();
    }
}


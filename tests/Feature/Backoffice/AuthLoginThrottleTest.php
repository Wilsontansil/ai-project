<?php

namespace Tests\Feature\Backoffice;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthLoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    private const TEST_EMAIL = 'admin@example.com';
    private const TEST_PASSWORD = 'correct-password';
    private const WRONG_PASSWORD = 'wrong-password';

    public function test_login_is_locked_after_five_failed_attempts(): void
    {
        User::factory()->create([
            'email' => self::TEST_EMAIL,
            'password' => self::TEST_PASSWORD,
        ]);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $response = $this->from(route('login'))->post(route('backoffice.login.submit'), [
                'email' => self::TEST_EMAIL,
                'password' => self::WRONG_PASSWORD,
            ]);

            $response->assertRedirect(route('login'));
        }

        $lockedResponse = $this->from(route('login'))->post(route('backoffice.login.submit'), [
            'email' => self::TEST_EMAIL,
            'password' => self::WRONG_PASSWORD,
        ]);

        $lockedResponse
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'email' => __('backoffice.pages.login.locked', ['minutes' => 15]),
            ]);
    }

    public function test_successful_login_clears_email_and_ip_throttle_counters(): void
    {
        User::factory()->create([
            'email' => self::TEST_EMAIL,
            'password' => self::TEST_PASSWORD,
        ]);

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            $this->from(route('login'))->post(route('backoffice.login.submit'), [
                'email' => self::TEST_EMAIL,
                'password' => self::WRONG_PASSWORD,
            ]);
        }

        $response = $this->post(route('backoffice.login.submit'), [
            'email' => self::TEST_EMAIL,
            'password' => self::TEST_PASSWORD,
        ]);

        $response->assertRedirect(route('backoffice.dashboard'));
        $this->assertAuthenticated();
        $this->assertSame(0, RateLimiter::attempts('backoffice-login:email:' . self::TEST_EMAIL));
        $this->assertSame(0, RateLimiter::attempts('backoffice-login:ip:127.0.0.1'));
    }
}

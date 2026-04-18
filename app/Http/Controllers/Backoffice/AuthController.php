<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOCKOUT_SECONDS = 900;

    public function showLoginForm(): View
    {
        return view('backoffice.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $email = Str::lower($credentials['email']);

        $lockoutResponse = $this->checkForActiveLockout($request, $email);

        if ($lockoutResponse !== null) {
            return $lockoutResponse;
        }

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return $this->handleFailedLogin($request, $email);
        }

        $this->clearLoginAttempts($request, $email);
        $request->session()->regenerate();

        // Single-session: store active session so other sessions are invalidated
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->update(['active_session_id' => $request->session()->getId()]);

        return redirect()->route('backoffice.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function hasTooManyLoginAttempts(Request $request, string $email): bool
    {
        return RateLimiter::tooManyAttempts($this->emailThrottleKey($email), self::MAX_LOGIN_ATTEMPTS)
            || RateLimiter::tooManyAttempts($this->ipThrottleKey($request), self::MAX_LOGIN_ATTEMPTS);
    }

    private function checkForActiveLockout(Request $request, string $email): ?RedirectResponse
    {
        if (! $this->hasTooManyLoginAttempts($request, $email)) {
            return null;
        }

        return $this->sendLockoutResponse($request, $email);
    }

    private function handleFailedLogin(Request $request, string $email): RedirectResponse
    {
        $this->incrementLoginAttempts($request, $email);

        Log::warning('Backoffice login failed.', [
            'email' => $email,
            'ip' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'email_attempts' => RateLimiter::attempts($this->emailThrottleKey($email)),
            'ip_attempts' => RateLimiter::attempts($this->ipThrottleKey($request)),
        ]);

        return $this->checkForActiveLockout($request, $email)
            ?? back()
                ->withErrors(['email' => 'Email atau password tidak valid.'])
                ->onlyInput('email');
    }

    private function incrementLoginAttempts(Request $request, string $email): void
    {
        RateLimiter::hit($this->emailThrottleKey($email), self::LOCKOUT_SECONDS);
        RateLimiter::hit($this->ipThrottleKey($request), self::LOCKOUT_SECONDS);
    }

    private function clearLoginAttempts(Request $request, string $email): void
    {
        RateLimiter::clear($this->emailThrottleKey($email));
        RateLimiter::clear($this->ipThrottleKey($request));
    }

    private function sendLockoutResponse(Request $request, string $email): RedirectResponse
    {
        $seconds = max(
            RateLimiter::availableIn($this->emailThrottleKey($email)),
            RateLimiter::availableIn($this->ipThrottleKey($request))
        );

        Log::warning('Backoffice login temporarily locked.', [
            'email' => $email,
            'ip' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'retry_after_seconds' => $seconds,
        ]);

        return back()
            ->withErrors([
                'email' => __('backoffice.pages.login.locked', [
                    'minutes' => max(1, (int) ceil($seconds / 60)),
                ]),
            ])
            ->onlyInput('email');
    }

    private function emailThrottleKey(string $email): string
    {
        return 'backoffice-login:email:' . $email;
    }

    private function ipThrottleKey(Request $request): string
    {
        return 'backoffice-login:ip:' . ($request->ip() ?? 'unknown');
    }
}

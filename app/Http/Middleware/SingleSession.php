<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SingleSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            /** @var \App\Models\User $user */
            $user = Auth::user()->fresh();
            $currentSessionId = $request->session()->getId();

            if ($user->active_session_id && $user->active_session_id !== $currentSessionId) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->with('error', __('backoffice.auth.session_expired'));
            }
        }

        return $next($request);
    }
}

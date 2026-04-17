<?php

namespace App\Http\Middleware;

use App\Models\ProjectSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyLiveChatWebhook
{
    public function handle(Request $request, Closure $next): Response
    {
        // Allow challenge/verification GETs through — they have their own token check in the controller.
        $challenge = $request->input('challenge', $request->query('challenge', ''));
        if ($challenge !== '' && $challenge !== null) {
            return $next($request);
        }

        $secret = (string) ProjectSetting::getValue(
            'livechat_webhook_secret',
            config('services.livechat.webhook_secret', '')
        );

        // If no secret configured, skip verification (backward-compatible).
        if ($secret === '') {
            return $next($request);
        }

        $header = (string) $request->header('X-livechat-Token', '');

        if (!hash_equals($secret, $header)) {
            Log::warning('LiveChat webhook auth failed', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return $next($request);
    }
}

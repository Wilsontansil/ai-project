<?php

namespace App\Http\Middleware;

use App\Models\ProjectSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyTelegramWebhook
{
    /** Maximum age (seconds) of a request before it is rejected. */
    private const TIMESTAMP_TOLERANCE = 300;

    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) ProjectSetting::getValue(
            'telegram_webhook_secret',
            config('services.telegram.webhook_secret', '')
        );

        // If no secret configured, skip verification (backward-compatible).
        if ($secret === '') {
            return $next($request);
        }

        // Telegram sends this header when webhook is registered with secret_token.
        // @see https://core.telegram.org/bots/api#setwebhook
        $header = (string) $request->header('X-Telegram-Bot-Api-Secret-Token', '');

        if (!hash_equals($secret, $header)) {
            Log::warning('Telegram webhook auth failed', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Replay-attack prevention via optional timestamp header.
        $ts = $request->header('X-Webhook-Timestamp');

        if ($ts !== null && $ts !== '') {
            $requestTime = (int) $ts;

            if ($requestTime <= 0 || abs(time() - $requestTime) > self::TIMESTAMP_TOLERANCE) {
                Log::warning('Telegram webhook replay detected', [
                    'ip' => $request->ip(),
                ]);

                return response()->json(['error' => 'Request expired'], 403);
            }
        }

        return $next($request);
    }
}

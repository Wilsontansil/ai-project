<?php

namespace App\Http\Middleware;

use App\Models\ProjectSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyWhatsAppWebhook
{
    /** Maximum age (seconds) of a request before it is rejected. */
    private const TIMESTAMP_TOLERANCE = 300;

    public function handle(Request $request, Closure $next): Response
    {
        // Allow health-check GETs through without auth.
        if ($request->isMethod('get')) {
            return $next($request);
        }

        $secret = (string) ProjectSetting::getValue(
            'whatsapp_webhook_secret',
            config('services.whatsapp.webhook_secret', '')
        );

        // If no secret configured, skip verification (backward-compatible).
        if ($secret === '') {
            return $next($request);
        }

        $header = (string) $request->header('X-Secret-Token', '');

        if (!hash_equals($secret, $header)) {
            Log::warning('WhatsApp webhook auth failed', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Replay-attack prevention via optional timestamp header.
        $ts = $request->header('X-Webhook-Timestamp');

        if ($ts !== null && $ts !== '') {
            $requestTime = (int) $ts;

            if ($requestTime <= 0 || abs(time() - $requestTime) > self::TIMESTAMP_TOLERANCE) {
                Log::warning('WhatsApp webhook replay detected', [
                    'ip' => $request->ip(),
                ]);

                return response()->json(['error' => 'Request expired'], 403);
            }
        }

        return $next($request);
    }
}

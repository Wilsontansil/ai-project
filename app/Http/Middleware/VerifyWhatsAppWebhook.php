<?php

namespace App\Http\Middleware;

use App\Models\ProjectSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyWhatsAppWebhook
{
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

        return $next($request);
    }
}

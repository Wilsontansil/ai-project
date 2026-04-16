<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LiveChatController extends Controller
{
    public function handleWebhook(Request $request)
    {
        if ($request->isMethod('get')) {
            $expectedToken = (string) config('services.livechat.verify_token', '');
            $providedToken = (string) $request->query('token', '');
            $challenge = (string) $request->query('challenge', '');

            if ($expectedToken === '' || $providedToken !== $expectedToken) {
                return response('', 401);
            }

            return response($challenge, 200)
                ->header('Content-Type', 'text/plain');
        }

        Log::info('Received LiveChat webhook', ['payload' => $request->all()]);

        return response()->json(['status' => 'ok']);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LiveChatController extends Controller
{
    public function handleWebhook(Request $request)
    {
        Log::info('Received LiveChat webhook', ['payload' => $request->all()]);
        $challenge = $request->input('challenge');

        return $challenge ? response($challenge, 200) : response()->json(['status' => 'ok']);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppController extends Controller
{
    public function handleWebhook(Request $request)
    {
        Log::info('Received WhatsApp webhook', ['payload' => $request->all()]);
        // Handle WhatsApp webhook here
        return response()->json(['status' => 'ok']);
    }
}

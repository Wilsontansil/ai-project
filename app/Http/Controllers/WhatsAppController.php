<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WhatsAppController extends Controller
{
    public function handleWebhook(Request $request)
    {
        // Handle WhatsApp webhook here
        return response()->json(['status' => 'ok']);
    }
}

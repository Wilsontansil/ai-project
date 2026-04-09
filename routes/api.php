<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TelegramController;
use App\Http\Controllers\WhatsAppController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');
Route::get('/test', function () {
    return response()->json(['message' => 'API is working']);
});
Route::post('/telegram/webhook', [TelegramController::class, 'handleWebhook']);
Route::match(['get', 'post'], '/whatsapp/webhook', [WhatsAppController::class, 'handleWebhook']);
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TelegramController;
use App\Http\Controllers\WhatsAppController;
use App\Http\Controllers\LiveChatController;

Route::get('/test', function () {
    return response()->json(['message' => 'API is working']);
});
Route::post('/telegram/webhook', [TelegramController::class, 'handleWebhook']);
Route::match(['get', 'post'], '/whatsapp/webhook', [WhatsAppController::class, 'handleWebhook']);
Route::match(['get', 'post'], '/livechat/webhook', [LiveChatController::class, 'handleWebhook']);
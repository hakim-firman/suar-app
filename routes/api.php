<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WhatsappBotController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/whatsapp/webhook', [WhatsappBotController::class, 'handleWebhook']);
Route::post('/whatsapp/callback', [WhatsappBotController::class, 'handleCallback']);


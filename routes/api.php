<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\Api\DataViewerController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// 🛡️ BÚNKER ACTIVADO: Límite de 300 peticiones por minuto para proteger el procesador
Route::middleware('throttle:300,1')->group(function () {
    Route::get('/webhook', [WebhookController::class, 'verify']);
    Route::post('/webhook', [WebhookController::class, 'process']);
});

// Rutas de depuración (Para ver los JSONs mágicos xd)
Route::get('/mensajes', [DataViewerController::class, 'getMessages']);
Route::get('/mensajes/{id}', [DataViewerController::class, 'showMessage']);
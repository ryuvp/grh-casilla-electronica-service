<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MensajeController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('remoteauth')->group(function () {
    Route::get('/mensajes/entrada', [MensajeController::class, 'bandejaEntrada']);
    Route::get('/mensajes/enviados', [MensajeController::class, 'bandejaEnviados']);
    Route::post('/mensajes', [MensajeController::class, 'store']);
    Route::get('/mensajes/{mensaje}', [MensajeController::class, 'show']);
    Route::post('/mensajes/{mensaje}/leido', [MensajeController::class, 'marcarLeido']);
});

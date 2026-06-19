<?php

use App\Http\Controllers\CasillaController;
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

Route::get('/health', function () {
    $payload = [
        'status' => 'ok',
        'service' => 'casilla-electronica-service',
        'timestamp' => now()->toIso8601String(),
    ];

    if (!app()->environment('production')) {
        $payload['php_version'] = PHP_VERSION;
        $payload['laravel_version'] = app()->version();
        $payload['app_env'] = app()->environment();
    }

    return response()->json($payload);
});

Route::middleware('remoteauth')->group(function () {
    Route::apiResource('/casillas', CasillaController::class);
    Route::get('/mensajes/verificar-envios', [MensajeController::class, 'verificarEnvios']);
    Route::get('/mensajes/entrada', [MensajeController::class, 'bandejaEntrada']);
    Route::get('/mensajes/destacados', [MensajeController::class, 'bandejaDestacados']);
    Route::get('/mensajes/archivados', [MensajeController::class, 'bandejaArchivados']);
    Route::get('/mensajes/enviados', [MensajeController::class, 'bandejaEnviados']);
    Route::post('/mensajes', [MensajeController::class, 'store']);
    Route::get('/mensajes/{mensaje}/certificado-pdf', [MensajeController::class, 'generarCertificadoPdf']);
    Route::get('/mensajes/{mensaje}/constancia-envio-pdf', [MensajeController::class, 'generarConstanciaEnvioPdf']);
    Route::get('/mensajes/{mensaje}/constancia-lectura-pdf', [MensajeController::class, 'generarConstanciaLecturaPdf']);
    Route::get('/mensajes/{mensaje}', [MensajeController::class, 'show']);
    Route::put('/mensajes/{mensaje}', [MensajeController::class, 'update']);
    Route::patch('/mensajes/{mensaje}', [MensajeController::class, 'update']);
    Route::delete('/mensajes/{mensaje}', [MensajeController::class, 'destroy']);
    Route::post('/mensajes/{mensaje}/leido', [MensajeController::class, 'marcarLeido']);
    Route::post('/mensajes/{mensaje}/destacar', [MensajeController::class, 'toggleDestacado']);
    Route::post('/mensajes/{mensaje}/archivar', [MensajeController::class, 'toggleArchivado']);
});

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Client\RequestException;

class RemoteAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        //\Log::info("Token recibido: " . $token);

        if (!$token) {
            return response()->json(['error' => 'Token no proporcionado'], 401);
        }

        $authServiceUrl = env('AUTH_SERVICE_URL') . '/api/usuario';

        try {
            $response = Http::withToken($token)->get($authServiceUrl);

            if ($response->unauthorized() || $response->forbidden()) {
                return response()->json(['message' => 'Token inválido'], 401);
            }

            // Puedes compartir datos del usuario si quieres
          //  \Log::info("Respuesta auth-service: " . $response->body());
            $request->merge(['auth_user' => $response->json()]);

            return $next($request);
        } catch (RequestException $e) {
            return response()->json([
                'error' => 'No se pudo conectar al servicio de autenticación',
                'details' => $e->getMessage()
            ], 503);
        }
    }
}

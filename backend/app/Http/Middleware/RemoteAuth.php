<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class RemoteAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Token no proporcionado'], 401);
        }

        $tokenHash = hash('sha256', $token);
        $designacionId = $request->header('X-Designacion-Id')
            ?? $request->header('X-Designacion-Logeada-Id')
            ?? $request->header('x-designacion-id')
            ?? $request->header('x-designacion-logeada-id');

        $cacheKey = "auth_user_{$tokenHash}" . ($designacionId ? "_desig_{$designacionId}" : '');
        $userData = Cache::get($cacheKey);

        if ($userData) {
            $cachedDesignacionId = $userData['designacion_logeada_id']
                ?? $userData['designacion_logeada']['id']
                ?? null;

            if ($designacionId && (int) $cachedDesignacionId !== (int) $designacionId) {
                Cache::forget($cacheKey);
                Cache::forget("auth_user_{$tokenHash}");
                $userData = null;
            }

            if ($userData) {
                $request->merge(['auth_user' => $userData]);
                return $next($request);
            }
        }

        try {
            $authServiceUrl = config('services.auth.url') . '/api/usuario';
            $httpRequest = Http::timeout(5)->withToken($token);

            if ($designacionId) {
                $httpRequest->withHeaders(['X-Designacion-Id' => $designacionId]);
            }

            $response = $httpRequest->get($authServiceUrl);

            if ($response->status() === 409) {
                return response()->json($response->json(), 409);
            }

            if ($response->unauthorized() || $response->forbidden()) {
                return response()->json(['message' => 'Token inválido'], 401);
            }

            $userData = $response->json();
            $currentDesignacionId = $userData['designacion_logeada_id']
                ?? $userData['designacion_logeada']['id']
                ?? null;

            // Cache de corta duración para alto rendimiento en ráfagas.
            // Se guarda también bajo la clave "plana" (sin designación): antes
            // solo se guardaba la variante "_desig_{id}", por lo que cualquier
            // request SIN header X-Designacion-Id nunca encontraba cache y
            // golpeaba Auth Service en cada llamada.
            Cache::put("auth_user_{$tokenHash}", $userData, 60);
            if ($currentDesignacionId) {
                Cache::put("auth_user_{$tokenHash}_desig_{$currentDesignacionId}", $userData, 60);
            }

            $request->merge(['auth_user' => $userData]);
            return $next($request);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'No se pudo conectar al servicio de autenticación',
                'details' => $e->getMessage()
            ], 503);
        }
    }
}

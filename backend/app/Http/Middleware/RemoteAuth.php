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
        $userData = Cache::get("auth_user_{$tokenHash}");

        if ($userData) {
            $request->merge(['auth_user' => $userData]);
            return $next($request);
        }

        try {
            $authServiceUrl = config('services.auth.url') . '/api/usuario';
            $response = Http::timeout(3)->withToken($token)->get($authServiceUrl);

            if ($response->unauthorized() || $response->forbidden()) {
                return response()->json(['message' => 'Token inválido'], 401);
            }

            $userData = $response->json();
            Cache::put("auth_user_{$tokenHash}", $userData, 600);

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

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Client\RequestException;
use App\Models\Casilla;

class RemoteAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken() ?: $request->query('token');
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
            $userData = $response->json();
            $request->merge(['auth_user' => $userData]);

            // Auto-creación de casilla si la designación activa no posee una en la base de datos
            $designacionId = $this->resolveDesignacionId($userData);
            if ($designacionId) {
                $this->ensureCasillaExists($designacionId);
            }

            return $next($request);
        } catch (ConnectionException $e) {
            return response()->json([
                'error' => 'No se pudo conectar al servicio de autenticación',
                'details' => $e->getMessage()
            ], 503);
        } catch (RequestException $e) {
            return response()->json([
                'error' => 'No se pudo conectar al servicio de autenticación',
                'details' => $e->getMessage()
            ], 503);
        }
    }

    private function resolveDesignacionId(?array $authUser): ?int
    {
        if (!$authUser) {
            return null;
        }

        $candidateIds = [
            data_get($authUser, 'designacion_logeada.id'),
            data_get($authUser, 'designacion_logeada_id'),
            data_get($authUser, 'designacion_id'),
        ];

        foreach ($candidateIds as $candidate) {
            if (is_numeric($candidate) && (int) $candidate > 0) {
                return (int) $candidate;
            }
        }

        $designaciones = data_get($authUser, 'designaciones_activas', data_get($authUser, 'designaciones', []));
        if (is_array($designaciones) && !empty($designaciones)) {
            $first = $designaciones[0] ?? null;
            $firstId = is_array($first) ? ($first['id'] ?? null) : null;

            if (is_numeric($firstId) && (int) $firstId > 0) {
                return (int) $firstId;
            }
        }

        return null;
    }

    private function ensureCasillaExists(int $designacionId): void
    {
        $exists = Casilla::where('designacion_id', $designacionId)->exists();
        if (!$exists) {
            try {
                Casilla::create([
                    'designacion_id' => $designacionId,
                    'numero'         => 'CAS-' . $designacionId,
                    'activo'         => true,
                    'fecha_inicio'   => now()->toDateString(),
                ]);
            } catch (\Exception $e) {
                \Log::error("Error auto-creando casilla para designación {$designacionId}: " . $e->getMessage());
            }
        }
    }
}

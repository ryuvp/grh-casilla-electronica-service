<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resuelve la identidad del portador del token consultando auth-service.
 *
 * Esta clase es el punto de acoplamiento entre este servicio y auth-service, y
 * por eso es el lugar donde una caida de Auth se convierte (o no) en una caida
 * de este servicio. Tres propiedades son deliberadas:
 *
 *  1. Solo una respuesta 2xx con forma de usuario se acepta como identidad. La
 *     version anterior hacia `$userData = $response->json()` sin comprobar el
 *     codigo, asi que un 500/502 de Auth inyectaba su cuerpo de error como
 *     'auth_user' valido: las peticiones seguian hacia los controladores con
 *     una identidad basura y sin 'id', lo que ademas hacia caer al RateLimiter
 *     en su fallback por IP.
 *
 *  2. Un unico request por token resuelve el miss (lock anti-estampida). Con
 *     TTL corto, al expirar la entrada todas las peticiones concurrentes del
 *     mismo token hacian miss a la vez y golpeaban Auth en bloque, justo cuando
 *     Auth ya estaba saturado.
 *
 *  3. Si Auth no responde se sirve la ultima identidad conocida dentro de una
 *     ventana corta ('stale_ttl') en lugar de devolver 503. En la corrida de
 *     estres el 503 de este catch fue el modo de fallo dominante: Auth se
 *     degradaba y SGD/File lo convertian en una caida propia.
 */
class RemoteAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Token no proporcionado'], 401);
        }

        $designacionHeader = $this->designacionSolicitada($request);
        $tokenHash = hash('sha256', $token);

        $cacheKey = $this->claveFresca($tokenHash, $designacionHeader);
        $staleKey = $this->claveStale($tokenHash, $designacionHeader);

        $userData = $this->leerDeCache($cacheKey, $tokenHash, $designacionHeader);

        if ($userData === null) {
            $resuelto = $this->resolver($token, $tokenHash, $designacionHeader, $cacheKey, $staleKey);

            // Un Response aqui es un corte de flujo (401 / 409 / 503).
            if ($resuelto instanceof Response) {
                return $resuelto;
            }

            $userData = $resuelto;
        }

        $request->merge(['auth_user' => $userData]);

        return $next($request);
    }

    /**
     * Lee la entrada fresca y la descarta si no corresponde a la designacion pedida.
     */
    private function leerDeCache(string $cacheKey, string $tokenHash, ?string $designacionHeader): ?array
    {
        $userData = Cache::get($cacheKey);

        if (!$this->tieneFormaDeUsuario($userData)) {
            return null;
        }

        if ($designacionHeader === null) {
            return $userData;
        }

        $cachedDesigId = $this->designacionDe($userData);

        if ($cachedDesigId !== null && (int) $cachedDesigId !== (int) $designacionHeader) {
            Cache::forget($cacheKey);
            Cache::forget($this->claveFresca($tokenHash, null));

            return null;
        }

        return $userData;
    }

    /**
     * Resuelve la identidad contra auth-service, serializando los miss concurrentes
     * del mismo token para no amplificar la carga sobre Auth.
     *
     * @return array|\Symfony\Component\HttpFoundation\Response
     */
    private function resolver(string $token, string $tokenHash, ?string $designacionHeader, string $cacheKey, string $staleKey)
    {
        // TTL del lock por encima del peor caso de la llamada a Auth, para que no
        // expire mientras su dueno sigue esperando respuesta.
        $ttlLock = (int) config('services.auth.connect_timeout', 2)
            + (int) config('services.auth.timeout', 5) + 5;

        $lock = Cache::lock('auth_user_lock_' . $tokenHash, $ttlLock);

        try {
            $lock->block((int) config('services.auth.lock_wait', 3));
        } catch (LockTimeoutException) {
            // Otro request ya fue a Auth por este mismo token y todavia no
            // termina. Si alcanzo a poblar la cache, su resultado sirve.
            $userData = $this->leerDeCache($cacheKey, $tokenHash, $designacionHeader);

            if ($userData !== null) {
                return $userData;
            }

            // Si el duenyo del lock tarda mas que lock_wait, lo normal es que Auth
            // este degradado. Preferir la copia degradada antes que ir a Auth:
            // llamar aqui multiplicaria las peticiones contra un servicio que ya
            // no da abasto, que es justo lo que este lock existe para evitar.
            $stale = Cache::get($staleKey);

            if ((int) config('services.auth.stale_ttl', 60) > 0 && $this->tieneFormaDeUsuario($stale)) {
                return $this->degradarOFallar($staleKey, $designacionHeader, 'lock no disponible');
            }

            // Sin nada en cache (arranque en frio): no queda mas que preguntar.
            return $this->consultarAuthService($token, $tokenHash, $designacionHeader, $cacheKey, $staleKey);
        }

        try {
            // Revalidar dentro del lock: el request que lo tenia antes pudo
            // haber poblado la cache mientras este esperaba.
            $userData = $this->leerDeCache($cacheKey, $tokenHash, $designacionHeader);

            if ($userData !== null) {
                return $userData;
            }

            return $this->consultarAuthService($token, $tokenHash, $designacionHeader, $cacheKey, $staleKey);
        } finally {
            $lock->release();
        }
    }

    /**
     * @return array|\Symfony\Component\HttpFoundation\Response
     */
    private function consultarAuthService(string $token, string $tokenHash, ?string $designacionHeader, string $cacheKey, string $staleKey)
    {
        try {
            $httpRequest = Http::withToken($token)
                ->connectTimeout((int) config('services.auth.connect_timeout', 2))
                ->timeout((int) config('services.auth.timeout', 5))
                ->acceptJson();

            if ($designacionHeader) {
                $httpRequest->withHeaders(['X-Designacion-Id' => $designacionHeader]);
            }

            $response = $httpRequest->get(rtrim((string) config('services.auth.url'), '/') . '/api/usuario');
        } catch (ConnectionException $e) {
            return $this->degradarOFallar($staleKey, $designacionHeader, 'conexion: ' . $e->getMessage());
        } catch (\Throwable $e) {
            return $this->degradarOFallar($staleKey, $designacionHeader, 'excepcion: ' . $e->getMessage());
        }

        // 409 = el usuario debe elegir designacion. Es una respuesta de negocio
        // valida de Auth y se propaga tal cual al cliente.
        if ($response->status() === 409) {
            return response()->json($response->json(), 409);
        }

        // Token invalido / sin permisos: decision firme de Auth, no se degrada.
        if ($response->unauthorized() || $response->forbidden()) {
            Cache::forget($staleKey);

            return response()->json(['message' => 'Token inválido'], 401);
        }

        // Cualquier otro no-2xx (500, 502, 503, 504...) es Auth fallando, no una
        // identidad. Antes se inyectaba el cuerpo de error como 'auth_user'.
        if (!$response->successful()) {
            return $this->degradarOFallar($staleKey, $designacionHeader, 'HTTP ' . $response->status());
        }

        $userData = $response->json();

        if (!$this->tieneFormaDeUsuario($userData)) {
            return $this->degradarOFallar($staleKey, $designacionHeader, 'respuesta 2xx sin forma de usuario');
        }

        $this->guardar($userData, $tokenHash);

        return $userData;
    }

    /**
     * Sirve la ultima identidad conocida si cabe en la ventana de degradacion;
     * si no, devuelve 503.
     *
     * @return array|\Symfony\Component\HttpFoundation\Response
     */
    private function degradarOFallar(string $staleKey, ?string $designacionHeader, string $motivo)
    {
        $stale = Cache::get($staleKey);

        if ((int) config('services.auth.stale_ttl', 60) > 0 && $this->tieneFormaDeUsuario($stale)) {
            $desigStale = $this->designacionDe($stale);

            // No se degrada a una designacion distinta de la pedida: seria
            // servir permisos de otro contexto organizacional.
            if ($designacionHeader === null || $desigStale === null || (int) $desigStale === (int) $designacionHeader) {
                $this->registrar('warning', 'RemoteAuth: auth-service no disponible, se sirve identidad en caché degradada', [
                    'motivo' => $motivo,
                    'usuario_id' => $stale['id'] ?? null,
                ]);

                return $stale;
            }
        }

        $this->registrar('error', 'RemoteAuth: auth-service no disponible y sin identidad en caché', ['motivo' => $motivo]);

        return response()->json([
            'error' => 'No se pudo conectar al servicio de autenticación',
        ], 503);
    }

    /**
     * Guarda la entrada fresca (TTL corto, control de permisos desactualizados)
     * y la copia degradada (TTL mayor, solo como red de seguridad ante caidas).
     */
    private function guardar(array $userData, string $tokenHash): void
    {
        $ttl = (int) config('services.auth.cache_ttl', 15);
        $staleTtl = (int) config('services.auth.stale_ttl', 60);
        $desigId = $this->designacionDe($userData);

        $claves = [null];

        if ($desigId !== null) {
            $claves[] = (string) $desigId;
        }

        foreach ($claves as $desig) {
            Cache::put($this->claveFresca($tokenHash, $desig), $userData, $ttl);

            if ($staleTtl > 0) {
                Cache::put($this->claveStale($tokenHash, $desig), $userData, $staleTtl);
            }
        }
    }

    /**
     * Registra sin poder romper la peticion.
     *
     * Este middleware decide si una peticion esta autenticada, y sus caminos de
     * error son justamente los que escriben al log. Si el logging falla (disco
     * lleno, permisos del volumen, NFS intermitente) Monolog lanza, y esa
     * excepcion convertiria un 503 controlado o una respuesta degradada valida en
     * un 500 inesperado. El resultado HTTP no debe depender de que el log escriba.
     */
    private function registrar(string $nivel, string $mensaje, array $contexto = []): void
    {
        try {
            Log::log($nivel, $mensaje, $contexto);
        } catch (\Throwable) {
            // Intencionalmente ignorado: ver el comentario de arriba.
        }
    }

    private function designacionSolicitada(Request $request): ?string
    {
        $valor = $request->header('X-Designacion-Id')
            ?? $request->header('X-Designacion-Logeada-Id')
            ?? $request->query('designacion_id');

        return $valor !== null && $valor !== '' ? (string) $valor : null;
    }

    private function designacionDe(mixed $userData): int|string|null
    {
        if (!is_array($userData)) {
            return null;
        }

        return $userData['designacion_logeada_id']
            ?? $userData['designacion_logeada']['id']
            ?? null;
    }

    /**
     * Una identidad util es un array con 'id'. Es exactamente la condicion que
     * el RateLimiter necesita para contar el cupo por usuario en vez de por IP.
     */
    private function tieneFormaDeUsuario(mixed $userData): bool
    {
        return is_array($userData) && !empty($userData['id']);
    }

    private function claveFresca(string $tokenHash, ?string $designacion): string
    {
        return 'auth_user_' . $tokenHash . ($designacion !== null ? '_desig_' . $designacion : '');
    }

    private function claveStale(string $tokenHash, ?string $designacion): string
    {
        return $this->claveFresca($tokenHash, $designacion) . '_stale';
    }
}

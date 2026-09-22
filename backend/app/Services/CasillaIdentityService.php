<?php

namespace App\Services;

use App\Models\Casilla;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * CasillaIdentityService.
 *
 * Concentra toda la resolucion de identidad de una Casilla: quien es (a
 * partir del payload de auth inyectado por RemoteAuth), y como se
 * resuelve/crea/enlaza su casilla.
 *
 * Regla central de este servicio: la Casilla identifica a una PERSONA
 * (usuario_id de Auth Service), nunca a un cargo/designacion. Una designacion
 * cambia cada vez que alguien asciende, rota de dependencia o termina un
 * encargo; el usuario_id es estable durante toda la relacion de la persona
 * con la entidad. `designacion_id` se conserva en la fila solo como dato de
 * referencia (la mas reciente conocida), para que herramientas de busqueda
 * legadas que aun consultan por designacion puedan ubicar la casilla, pero
 * nunca participa en la resolucion de identidad.
 *
 * El campo `tipo` (interno/externo) es puramente informativo (para pantallas
 * administrativas); tampoco se infiere de si hay DNI disponible, porque el
 * personal interno tambien tiene DNI en Auth Service. El identificador
 * visible de la casilla (`numero`) tampoco distingue interno/externo: es
 * siempre `CASILLA-{dni|usuario_id}`.
 */
class CasillaIdentityService
{
    /**
     * Obtiene datos de usuario autenticado inyectados por RemoteAuth.
     */
    public function getAuthUser(Request $request): array
    {
        $authUser = $request->input('auth_user', []);

        return is_array($authUser) ? $authUser : [];
    }

    /**
     * Resuelve el id de Usuario (Auth Service) del solicitante autenticado.
     */
    public function getAuthUsuarioId(Request $request): ?int
    {
        $id = data_get($this->getAuthUser($request), 'id');

        return is_numeric($id) && (int) $id > 0 ? (int) $id : null;
    }

    /**
     * Resuelve el DNI (numero_documento) del usuario autenticado, cuando el
     * payload de auth lo incluye.
     */
    public function getAuthDni(Request $request): ?string
    {
        $dni = data_get($this->getAuthUser($request), 'numero_documento');

        return $dni ? (string) $dni : null;
    }

    /**
     * Resuelve la designacion actualmente activa del solicitante autenticado
     * (la que tiene seleccionada en esta sesion/peticion puntual). Se usa
     * unicamente para congelar el contexto de cargo/dependencia de un
     * remitente en el momento exacto de un envio (`Mensaje::designacion_origen_id`);
     * nunca para resolver identidad de la casilla, que es siempre por
     * `usuario_id`.
     */
    public function getAuthDesignacionId(Request $request): ?int
    {
        $id = data_get($this->getAuthUser($request), 'designacion_logeada.id');

        return is_numeric($id) && (int) $id > 0 ? (int) $id : null;
    }

    /**
     * Resuelve la casilla activa de la PERSONA autenticada. YA NO la crea
     * automaticamente: si todavia no tiene una, retorna null y el llamador
     * responde 403/estado "sin casilla" para que el frontend le muestre el
     * aviso de "crear mi casilla" (opt-in explicito, ver autoCrearCasillaPropia()).
     */
    public function getAuthCasilla(Request $request): ?Casilla
    {
        $usuarioId = $this->getAuthUsuarioId($request);
        if (!$usuarioId) {
            return null;
        }

        return $this->getActiveCasillaByUsuarioId($usuarioId);
    }

    /**
     * Crea la casilla de la PERSONA AUTENTICADA, a su propio pedido explicito
     * (p.ej. aceptó el aviso "¿Quieres crear tu casilla electrónica?"). Es el
     * unico camino por el que un usuario interno obtiene una casilla: nadie
     * mas puede creársela por él.
     */
    public function autoCrearCasillaPropia(Request $request): ?Casilla
    {
        $usuarioId = $this->getAuthUsuarioId($request);
        if (!$usuarioId) {
            return null;
        }

        $existente = $this->getActiveCasillaByUsuarioId($usuarioId);
        if ($existente) {
            return $existente;
        }

        $authUser = $this->getAuthUser($request);
        $nombre = trim((string) data_get($authUser, 'nombre') . ' ' . (string) data_get($authUser, 'apellido'));
        $esExterno = data_get($authUser, 'es_externo');

        $casilla = $this->resolveOrCreateCasillaPersona(
            $usuarioId,
            $this->getAuthDni($request),
            $nombre !== '' ? $nombre : null,
            is_bool($esExterno) ? $esExterno : null,
            permitirCrear: true
        );

        $designacionActualId = data_get($authUser, 'designacion_logeada.id');
        if ($casilla && is_numeric($designacionActualId) && !$casilla->designacion_id) {
            $casilla->update(['designacion_id' => (int) $designacionActualId]);
        }

        return $casilla;
    }

    /**
     * Resuelve la casilla activa de una persona por su usuario_id.
     */
    public function getActiveCasillaByUsuarioId(int $usuarioId): ?Casilla
    {
        return Casilla::where('usuario_id', $usuarioId)
            ->where('activo', true)
            ->where(function ($query) {
                $query->whereNull('fecha_fin')
                    ->orWhereDate('fecha_fin', '>=', now()->toDateString());
            })
            ->first();
    }

    /**
     * Resuelve la casilla YA EXISTENTE de la PERSONA que ocupa una designacion,
     * consultando a Auth Service (fetchActorDetailsByDesignacionId) el
     * usuario_id/DNI real detras de esa designacion. NUNCA crea una casilla
     * nueva: nadie puede crearle casilla a otra persona sin su consentimiento
     * (ver autoCrearCasillaPropia(), el unico camino de creacion para
     * usuarios internos). Si la persona aun no tiene casilla, retorna null.
     */
    public function resolveCasillaPorDesignacion(int $designacionId, ?string $token): ?Casilla
    {
        $actor = $this->fetchActorDetailsByDesignacionId($designacionId, $token);
        $usuarioId = data_get($actor, 'usuario_id');

        if (!$usuarioId) {
            Log::error("No se pudo resolver el usuario detras de la designación {$designacionId} via Auth Service.");

            return null;
        }

        $esPersonaNatural = data_get($actor, 'is_persona_natural');
        $casilla = $this->resolveOrCreateCasillaPersona(
            (int) $usuarioId,
            data_get($actor, 'numero_documento'),
            data_get($actor, 'usuario_nombre'),
            is_bool($esPersonaNatural) ? $esPersonaNatural : null,
            permitirCrear: false
        );

        // designacion_id se guarda solo como dato de referencia (la mas
        // reciente por la que se ubico a esta persona): nunca se usa para
        // resolver identidad.
        if ($casilla && (int) $casilla->designacion_id !== $designacionId) {
            $casilla->update(['designacion_id' => $designacionId]);
        }

        return $casilla;
    }

    /**
     * Resuelve o crea la casilla de una PERSONA (nunca de un cargo/designacion).
     *
     * - Si se conoce su usuario_id (ya tiene cuenta en Auth Service) y ya
     *   tiene una casilla, la retorna directamente.
     * - Si no, reconcilia con una casilla ya creada solo por DNI (p.ej. se le
     *   notifico antes de que tuviera cuenta): la enlaza con su usuario_id en
     *   vez de crear una nueva y perder su historial.
     * - Si no existe ninguna, crea una nueva con los datos disponibles.
     */
    /**
     * @param bool $permitirCrear Si es false, SOLO resuelve una casilla ya
     * existente (o la enlaza por DNI si ya existia), pero nunca crea una
     * nueva. Una persona (interna o externa por consentimiento explicito via
     * autoCrearCasillaPropia()) es la unica que puede decidir tener casilla;
     * nadie mas puede creársela "para" ella sin que lo pida.
     */
    public function resolveOrCreateCasillaPersona(?int $usuarioId, ?string $dni, ?string $nombre, ?bool $esExterno = null, bool $permitirCrear = true): ?Casilla
    {
        if ($usuarioId) {
            $casilla = Casilla::where('usuario_id', $usuarioId)->first();
            if ($casilla) {
                return $casilla;
            }
        }

        if ($dni) {
            $casillaPorDni = Casilla::where('dni', $dni)->first();
            if ($casillaPorDni) {
                if ($usuarioId && !$casillaPorDni->usuario_id) {
                    $casillaPorDni->update(['usuario_id' => $usuarioId]);
                }

                return $casillaPorDni->fresh();
            }
        }

        if (!$permitirCrear) {
            return null;
        }

        if (!$usuarioId && !$dni) {
            return null;
        }

        // Si no se indico explicitamente, se asume externo solo cuando la
        // persona todavia no tiene cuenta (usuario_id) en Auth Service.
        $tipo = ($esExterno ?? !$usuarioId) ? 'externo' : 'interno';

        try {
            return Casilla::create([
                'tipo' => $tipo,
                'usuario_id' => $usuarioId,
                'dni' => $dni,
                'nombre_externo' => $nombre,
                'numero' => 'CASILLA-' . ($dni ?: $usuarioId),
                'activo' => true,
                'fecha_inicio' => now()->toDateString(),
            ]);
        } catch (\Exception $e) {
            Log::error("Error auto-creando/enlazando casilla para usuario {$usuarioId}: " . $e->getMessage());

            return null;
        }
    }

    /**
     * Resuelve datos basicos (nombre, DNI, contacto) de la PERSONA duena de
     * una casilla, sin pasar por ninguna designacion: se usa para mostrar al
     * destinatario en las constancias PDF, ya que un destinatario es siempre
     * una persona (interna o externa) y nunca un cargo. Si la casilla ya
     * tiene `usuario_id`, se consulta a Auth Service por ese id; si todavia
     * es una persona externa sin cuenta, se usan los datos ya guardados en la
     * propia fila de `casillas` (nombre_externo/dni) sin llamadas HTTP.
     */
    public function resolvePersonaBasica(?Casilla $casilla, ?string $token): array
    {
        if (!$casilla) {
            return [];
        }

        $fallback = [
            'usuario_nombre' => $casilla->nombre_externo,
            'numero_documento' => $casilla->dni,
        ];

        if (!$casilla->usuario_id || !$token) {
            return $fallback;
        }

        $cacheKey = "casilla_persona_usr_{$casilla->usuario_id}";
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        // Se usa el endpoint de "resumen-basico" (no el show() completo de
        // usuarios) porque este ultimo exige designacion activa en Auth
        // Service, y el destinatario de una notificacion suele ser un
        // ciudadano/persona natural sin ninguna: si es él mismo viendo su
        // propia constancia, no puede tener una designacion activa.
        $url = env('AUTH_SERVICE_URL') . '/api/usuarios/' . $casilla->usuario_id . '/resumen-basico';
        try {
            $response = Http::withToken($token)->timeout(5)->get($url);
            if ($response->successful()) {
                $data = $response->json('data') ?? $response->json();
                $nombreCompleto = trim(($data['nombre'] ?? '') . ' ' . ($data['apellido'] ?? ''));

                $result = [
                    'usuario_nombre' => $nombreCompleto !== '' ? $nombreCompleto : $fallback['usuario_nombre'],
                    'numero_documento' => $data['numero_documento'] ?? $fallback['numero_documento'],
                    'email' => $data['email'] ?? null,
                    'telefono' => $data['telefono'] ?? null,
                ];
                Cache::put($cacheKey, $result, 300);

                return $result;
            }
        } catch (\Exception $e) {
            Log::error("Error obteniendo datos de persona para usuario {$casilla->usuario_id}: " . $e->getMessage());
        }

        return $fallback;
    }

    /**
     * Obtiene los detalles (usuario_id, nombre, DNI, cargo) de la persona
     * detras de una designacion, consultando Auth Service. Se usa tanto para
     * resolver identidad como para mostrar al REMITENTE (cargo/dependencia)
     * en los certificados PDF, congelado a la designacion que tenia activa
     * en el momento del envio (`Mensaje::designacion_origen_id`).
     */
    public function fetchActorDetailsByDesignacionId(?int $designacionId, ?string $token): array
    {
        if (!$designacionId || !$token) {
            return [];
        }

        // Cache corta: los datos de usuario/cargo de una designación cambian con
        // muy poca frecuencia, y esta función se invoca repetidamente (remitente
        // + destinatario) al generar certificados/constancias en PDF. No se
        // cachean fallos/respuestas vacías para no "congelar" un error transitorio.
        $cacheKey = "casilla_actor_desig_{$designacionId}";
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        $url = env('AUTH_SERVICE_URL') . '/api/designaciones/' . $designacionId . '/usuario-cargo';
        try {
            $response = Http::withToken($token)->timeout(5)->get($url);
            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data)) {
                    Cache::put($cacheKey, $data, 300);
                }

                return $data;
            }
        } catch (\Exception $e) {
            Log::error("Error fetching actor details for designacion {$designacionId}: " . $e->getMessage());
        }

        return [];
    }
}

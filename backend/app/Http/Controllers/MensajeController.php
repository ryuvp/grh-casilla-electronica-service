<?php

namespace App\Http\Controllers;

use App\Http\Resources\MensajeResource;
use App\Models\Casilla;
use App\Models\Mensaje;
use App\Models\MensajeDestinatario;
use App\Services\CasillaIdentityService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * MensajeController.
 *
 * Gestiona bandejas y operaciones de mensajes entre casillas.
 * La autorizacion por permisos/roles se resuelve en frontend.
 *
 * La resolucion de identidad de casilla (quien es el solicitante, cual es
 * su casilla, como se resuelve/crea/enlaza) vive en CasillaIdentityService:
 * ver esa clase para el porque del diseño (la casilla identifica a una
 * persona, no a un cargo/designacion).
 */
class MensajeController extends Controller
{
    public function __construct(private readonly CasillaIdentityService $casillaIdentity)
    {
    }

    /**
     * Calcula per_page dentro de limites operativos.
     */
    private function resolvePerPage(Request $request): int
    {
        $perPage = max((int) ($request->per_page ?? 10), 1);

        return min($perPage, 100);
    }

    /**
     * Construye una respuesta paginada estandar de mensajes.
     */
    private function paginateMensajes(Request $request, $query)
    {
        return MensajeResource::collection(
            $query
                ->with('adjuntos', 'destinatarios')
                ->filter($request)
                ->orderByDesc('created_at')
                ->paginate($this->resolvePerPage($request))
        );
    }

    /**
     * Determina si el usuario autenticado puede emitir notificaciones.
     */
    private function canWriteNotifications(Request $request): bool
    {
        if ($request->filled('sgd_referencias')) {
            return true;
        }

        $authUser = $this->casillaIdentity->getAuthUser($request);

        $roles = data_get($authUser, 'roles', []);
        if (!is_array($roles)) {
            return false;
        }

        foreach ($roles as $rol) {
            $name = strtolower((string) (data_get($rol, 'name', data_get($rol, 'nombre', data_get($rol, 'descripcion', '')))));

            if (str_contains($name, 'admin') || str_contains($name, 'notificador')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Restringe el query a mensajes donde la casilla dada es destinataria,
     * ya sea como destinatario primario (`casilla_destino_id`, compatibilidad
     * single-destinatario) o como uno mas de un envio multiple (tabla pivote
     * `mensaje_destinatarios`).
     */
    private function scopeDestinatario($query, int $casillaId)
    {
        return $query->where(function ($q) use ($casillaId) {
            $q->where('casilla_destino_id', $casillaId)
                ->orWhereHas('destinatarios', function ($sub) use ($casillaId) {
                    $sub->where('casilla_id', $casillaId);
                });
        });
    }

    /**
     * Indica si una casilla es destinataria de un mensaje ya cargado, sea como
     * destinatario primario (`casilla_destino_id`) o como uno mas de un envio
     * multiple (tabla pivote `mensaje_destinatarios`). Equivalente a
     * `scopeDestinatario` pero para un modelo ya en memoria (autorizacion de
     * acciones puntuales: ver, marcar leido, destacar, archivar, generar PDF).
     */
    private function esDestinatarioDelMensaje(Mensaje $mensaje, int $casillaId): bool
    {
        return $mensaje->casilla_destino_id === $casillaId
            || $mensaje->destinatarios()->where('casilla_id', $casillaId)->exists();
    }

    /**
    * Bandeja de entrada por casilla destino.
     *
     * Regla B-02: paginacion obligatoria con per_page=10 por defecto, maximo 100.
     */
    public function bandejaEntrada(Request $request)
    {
        $casillaAuth = $this->casillaIdentity->getAuthCasilla($request);
        if (!$casillaAuth) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se pudo resolver una casilla activa para la designacion autenticada',
            ], Response::HTTP_FORBIDDEN);
        }

        return $this->paginateMensajes(
            $request,
            $this->scopeDestinatario(Mensaje::query(), $casillaAuth->id)
                ->where('archivado', false)
        );
    }

    /**
    * Bandeja de mensajes destacados recibidos.
     */
    public function bandejaDestacados(Request $request)
    {
        $casillaAuth = $this->casillaIdentity->getAuthCasilla($request);
        if (!$casillaAuth) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se pudo resolver una casilla activa para la designacion autenticada',
            ], Response::HTTP_FORBIDDEN);
        }

        return $this->paginateMensajes(
            $request,
            $this->scopeDestinatario(Mensaje::query(), $casillaAuth->id)
                ->where('destacado', true)
                ->where('archivado', false)
        );
    }

    /**
    * Bandeja de mensajes archivados recibidos.
     */
    public function bandejaArchivados(Request $request)
    {
        $casillaAuth = $this->casillaIdentity->getAuthCasilla($request);
        if (!$casillaAuth) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se pudo resolver una casilla activa para la designacion autenticada',
            ], Response::HTTP_FORBIDDEN);
        }

        return $this->paginateMensajes(
            $request,
            $this->scopeDestinatario(Mensaje::query(), $casillaAuth->id)
                ->where('archivado', true)
        );
    }

    /**
    * Bandeja de salida por casilla origen.
     *
     * Regla B-02: paginacion obligatoria con per_page=10 por defecto, maximo 100.
     */
    public function bandejaEnviados(Request $request)
    {
        $casillaAuth = $this->casillaIdentity->getAuthCasilla($request);
        if (!$casillaAuth) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se pudo resolver una casilla activa para la designacion autenticada',
            ], Response::HTTP_FORBIDDEN);
        }

        return $this->paginateMensajes(
            $request,
            Mensaje::where('casilla_origen_id', $casillaAuth->id)
        );
    }

    /**
     * Verifica masivamente el envío y lectura de documentos del SGD.
     */
    public function verificarEnvios(Request $request)
    {
        $documentoIds = $request->input('documento_ids', []);
        if (!is_array($documentoIds) || empty($documentoIds)) {
            return response()->json([]);
        }

        // Limpieza de IDs para evitar desbordes y asegurar tipos enteros
        $documentoIds = array_values(array_unique(array_filter(array_map('intval', $documentoIds))));
        if (empty($documentoIds)) {
            return response()->json([]);
        }

        // Query optimizada conduciendo desde `adjuntos` como tabla principal (driving table)
        // para aprovechar el índice compuesto ['tipo', 'referencia_id'] en milisegundos,
        // evitando escaneos secuenciales sobre la tabla `mensajes`.
        $rows = \Illuminate\Support\Facades\DB::table('adjuntos as a')
            ->join('mensajes as m', function ($join) {
                $join->on('m.id', '=', 'a.mensaje_id')
                     ->whereNull('m.deleted_at');
            })
            ->where('a.tipo', 'documento_sgd')
            ->whereIn('a.referencia_id', $documentoIds)
            ->whereNull('a.deleted_at')
            ->select([
                'a.referencia_id as documento_id',
                'm.id            as mensaje_id',
                'm.leido',
                'm.created_at    as fecha_envio',
                'm.read_at       as fecha_lectura',
                'm.casilla_origen_id',
                'm.casilla_destino_id',
            ])
            ->get()
            ->keyBy('documento_id');

        $mapping = [];
        foreach ($documentoIds as $id) {
            $row = $rows->get($id);
            $mapping[$id] = $row ? [
                'enviado'           => true,
                'mensaje_id'        => $row->mensaje_id,
                'leido'             => (bool) $row->leido,
                'fecha_envio'       => $row->fecha_envio,
                'fecha_lectura'     => $row->fecha_lectura,
                'casilla_origen_id' => $row->casilla_origen_id,
                'casilla_destino_id'=> $row->casilla_destino_id,
            ] : null;
        }

        return response()->json($mapping);
    }

    /**
     * Muestra un mensaje si pertenece a la casilla origen o destino autenticada.
     */
    public function show(Request $request, Mensaje $mensaje)
    {
        $casillaAuth = $this->casillaIdentity->getAuthCasilla($request);
        if (!$casillaAuth) {
            return response()->json(['error' => 'No autorizado'], Response::HTTP_FORBIDDEN);
        }

        if ($mensaje->casilla_origen_id !== $casillaAuth->id && !$this->esDestinatarioDelMensaje($mensaje, $casillaAuth->id)) {
            return response()->json(['error' => 'No autorizado'], Response::HTTP_FORBIDDEN);
        }

        return new MensajeResource($mensaje->load('adjuntos', 'destinatarios'));
    }

    /**
     * Crea mensaje y referencias externas tipadas.
     *
     * Reglas de negocio:
     * - El mensaje se enruta entre casillas activas.
     * - Operacion atomica en transaccion.
     */
    public function store(Request $request)
    {
        if (!$request->filled('casilla_destino_id') && $request->filled('designacion_destino_id')) {
            $casillaDestino = $this->casillaIdentity->resolveOrCreateCasillaPorDesignacion(
                (int) $request->input('designacion_destino_id'),
                $request->bearerToken()
            );

            if ($casillaDestino) {
                $request->merge(['casilla_destino_id' => $casillaDestino->id]);
            }
        }

        if (!$this->canWriteNotifications($request)) {
            return response()->json([
                'status' => 'error',
                'message' => 'No autorizado para enviar notificaciones',
            ], Response::HTTP_FORBIDDEN);
        }

        $casillaAuth = $this->casillaIdentity->getAuthCasilla($request);
        if (!$casillaAuth) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se pudo resolver una casilla activa para la designacion autenticada',
            ], Response::HTTP_FORBIDDEN);
        }

        $validator = Validator::make($request->all(), Mensaje::$validables + [
            'archivo_ids' => 'nullable|array',
            'archivo_ids.*' => 'integer|distinct',
            'normatividad_referencias' => 'nullable|array',
            'normatividad_referencias.*.normatividad_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], Response::HTTP_BAD_REQUEST);
        }

        $validated = $validator->validated();
        $validated['casilla_origen_id'] = $casillaAuth->id;
        // Congela la designacion activa del remitente en este preciso envio
        // (cargo/dependencia con la que actua), independiente de que luego
        // cambie de designacion o maneje varias en paralelo.
        $validated['designacion_origen_id'] = $this->casillaIdentity->getAuthDesignacionId($request);

        $archivoIds = $validated['archivo_ids'] ?? [];
        $sgdReferencias = $validated['sgd_referencias'] ?? [];
        $normatividadReferencias = $validated['normatividad_referencias'] ?? [];
        $casillaDestinoIds = $validated['casilla_destino_ids'] ?? [];
        $administradosExternos = $validated['administrados_externos'] ?? [];
        $designacionesDestinoIds = $validated['designaciones_destino_ids'] ?? [];

        unset(
            $validated['archivo_ids'],
            $validated['sgd_referencias'],
            $validated['normatividad_referencias'],
            $validated['casilla_destino_ids'],
            $validated['administrados_externos'],
            $validated['designaciones_destino_ids'],
        );

        // Resuelve/crea la casilla de la PERSONA detras de cada designacion
        // adicional indicada (p.ej. cuando el consumidor -como el modulo
        // "Enviar a Casilla" del SGD- conoce designaciones destino pero no sus
        // casilla_id ni el usuario_id real). Si la resolucion/creacion falla
        // (p.ej. error de BD o de Auth Service), se registra en
        // $destinatariosNoResueltos en vez de perderse en silencio.
        $destinatariosNoResueltos = [];
        $token = $request->bearerToken();

        foreach ($designacionesDestinoIds as $designacionId) {
            $casillaInterna = $this->casillaIdentity->resolveOrCreateCasillaPorDesignacion((int) $designacionId, $token);

            if ($casillaInterna) {
                $casillaDestinoIds[] = $casillaInterna->id;
            } else {
                $destinatariosNoResueltos[] = ['tipo' => 'interno', 'designacion_id' => (int) $designacionId];
            }
        }

        // Resuelve/crea la casilla externa (por DNI) de cada administrado indicado.
        // El formato de DNI ya fue validado (8 digitos numericos); la verificacion
        // de que el DNI corresponde a una persona real se hace en el frontend
        // contra el store de personas (regla 7.2 de AGENTS.md), ya que este
        // servicio no debe realizar llamadas HTTP sincronas a otros microservicios.
        foreach ($administradosExternos as $administrado) {
            $casillaExterna = $this->casillaIdentity->resolveOrCreateCasillaPersona(
                null,
                $administrado['dni'],
                $administrado['nombre'] ?? null,
                true
            );

            if ($casillaExterna && ($administrado['persona_id'] ?? null) && !$casillaExterna->persona_id) {
                $casillaExterna->update(['persona_id' => $administrado['persona_id']]);
            }

            if ($casillaExterna) {
                $casillaDestinoIds[] = $casillaExterna->id;
            } else {
                $destinatariosNoResueltos[] = ['tipo' => 'externo', 'dni' => $administrado['dni']];
            }
        }

        // Une destinatario primario (compatibilidad single-destinatario) con la
        // seleccion multiple, sin duplicados.
        $destinoIds = array_values(array_unique(array_filter(array_merge(
            [$validated['casilla_destino_id'] ?? null],
            $casillaDestinoIds
        ))));

        if (empty($destinoIds)) {
            return response()->json([
                'status' => 'error',
                'message' => !empty($destinatariosNoResueltos)
                    ? 'No se pudo crear/resolver la casilla de ningun destinatario indicado. Intente nuevamente.'
                    : 'Debe indicar al menos un destinatario (casilla_destino_id, casilla_destino_ids, designaciones_destino_ids o administrados_externos)',
                'destinatarios_no_resueltos' => $destinatariosNoResueltos,
            ], Response::HTTP_BAD_REQUEST);
        }

        // Valida que todas las casillas destino existan y esten activas.
        $casillasDestino = Casilla::whereIn('id', $destinoIds)
            ->where('activo', true)
            ->where(function ($query) {
                $query->whereNull('fecha_fin')
                    ->orWhereDate('fecha_fin', '>=', now()->toDateString());
            })
            ->get()
            ->keyBy('id');

        $idsInvalidos = array_values(array_diff($destinoIds, $casillasDestino->keys()->all()));
        if (!empty($idsInvalidos)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Una o mas casillas destinatarias no existen o no estan activas',
                'casilla_ids_invalidas' => $idsInvalidos,
            ], Response::HTTP_BAD_REQUEST);
        }

        // Nunca se puede notificar a la propia casilla del emisor: equivale a
        // derivarse un documento a si mismo, algo que tampoco esta permitido en
        // el resto del flujo del SGD (derivaciones). Esto cubre tanto el caso
        // directo (mismo casilla_id) como el caso donde el emisor aparece como
        // destinatario "externo" por compartir el mismo DNI (misma persona,
        // casilla distinta).
        $authDni = $this->casillaIdentity->getAuthDni($request);
        $destinoIdsSinAutoenvio = [];

        foreach ($destinoIds as $id) {
            $esUnoMismo = (int) $id === (int) $casillaAuth->id;
            $casilla = $casillasDestino->get($id);
            $mismoDni = $authDni && $casilla && $casilla->dni === $authDni;

            if ($esUnoMismo || $mismoDni) {
                $destinatariosNoResueltos[] = ['tipo' => 'auto_envio', 'casilla_id' => (int) $id];
                continue;
            }

            $destinoIdsSinAutoenvio[] = $id;
        }

        $destinoIds = $destinoIdsSinAutoenvio;

        if (empty($destinoIds)) {
            return response()->json([
                'status' => 'error',
                'message' => 'No puede notificarse a si mismo. Indique al menos un destinatario distinto de su propia casilla.',
                'destinatarios_no_resueltos' => $destinatariosNoResueltos,
            ], Response::HTTP_BAD_REQUEST);
        }

        // El destinatario primario mantiene compatibilidad con `casilla_destino_id`
        // (bandejas y certificados PDF de flujos single-destinatario ya existentes).
        $validated['casilla_destino_id'] = $destinoIds[0];

        try {
            DB::beginTransaction();

            $mensaje = Mensaje::create($validated);

            $rowsDestinatarios = [];
            foreach ($destinoIds as $casillaId) {
                $rowsDestinatarios[] = [
                    'mensaje_id' => $mensaje->id,
                    'casilla_id' => $casillaId,
                    'leido' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            MensajeDestinatario::insert($rowsDestinatarios);

            $rowsAdjuntos = [];

            foreach ($archivoIds as $archivoId) {
                $rowsAdjuntos[] = [
                    'mensaje_id' => $mensaje->id,
                    'referencia_id' => $archivoId,
                    'tipo' => 'archivo',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            foreach ($sgdReferencias as $referencia) {
                $rowsAdjuntos[] = [
                    'mensaje_id' => $mensaje->id,
                    'referencia_id' => $referencia['documento_id'],
                    'tipo' => 'documento_sgd',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            foreach ($normatividadReferencias as $referencia) {
                $rowsAdjuntos[] = [
                    'mensaje_id' => $mensaje->id,
                    'referencia_id' => $referencia['normatividad_id'],
                    'tipo' => 'normatividad',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!empty($rowsAdjuntos)) {
                DB::table('adjuntos')->insert($rowsAdjuntos);
            }

            DB::commit();

            $resource = new MensajeResource($mensaje->load('adjuntos', 'destinatarios'));

            // Si hubo destinatarios cuya casilla no se pudo crear/resolver pero
            // el mensaje igual se envio a los demas, se informa explicitamente
            // en vez de perder esa falla en silencio (el emisor debe poder
            // reintentar o corregir ese destinatario puntual).
            if (!empty($destinatariosNoResueltos)) {
                $resource = $resource->additional(['destinatarios_no_resueltos' => $destinatariosNoResueltos]);
            }

            return $resource->response()->setStatusCode(Response::HTTP_CREATED);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Error al guardar el mensaje: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Marca un mensaje como leido para la casilla autenticada.
     *
     * El estado de lectura se registra por destinatario (tabla pivote), ya que
     * un mismo mensaje puede tener multiples destinatarios y cada uno genera
     * su propia constancia de lectura. Se mantiene el espejo en `mensajes`
     * (leido/read_at) para el destinatario primario, por compatibilidad con
     * los flujos y certificados PDF de un solo destinatario.
     */
    public function marcarLeido(Request $request, Mensaje $mensaje)
    {
        $casillaAuth = $this->casillaIdentity->getAuthCasilla($request);
        $esDestinatario = $casillaAuth && $this->esDestinatarioDelMensaje($mensaje, $casillaAuth->id);

        if (!$esDestinatario) {
            return response()->json(['error' => 'No autorizado'], Response::HTTP_FORBIDDEN);
        }

        MensajeDestinatario::where('mensaje_id', $mensaje->id)
            ->where('casilla_id', $casillaAuth->id)
            ->update(['leido' => true, 'read_at' => now()]);

        if ($casillaAuth->id === $mensaje->casilla_destino_id) {
            $mensaje->update([
                'leido' => true,
                'read_at' => now(),
            ]);
        }

        return (new MensajeResource($mensaje->load('adjuntos', 'destinatarios')))->response();
    }

    /**
     * Alterna el estado destacado del mensaje.
     */
    public function toggleDestacado(Request $request, Mensaje $mensaje)
    {
        $casillaAuth = $this->casillaIdentity->getAuthCasilla($request);
        $esDestinatario = $casillaAuth && $this->esDestinatarioDelMensaje($mensaje, $casillaAuth->id);

        if (!$esDestinatario) {
            return response()->json(['error' => 'No autorizado'], Response::HTTP_FORBIDDEN);
        }

        $mensaje->update([
            'destacado' => !$mensaje->destacado,
        ]);

        return (new MensajeResource($mensaje->load('adjuntos', 'destinatarios')))->response();
    }

    /**
     * Alterna el estado archivado del mensaje.
     */
    public function toggleArchivado(Request $request, Mensaje $mensaje)
    {
        $casillaAuth = $this->casillaIdentity->getAuthCasilla($request);
        $esDestinatario = $casillaAuth && $this->esDestinatarioDelMensaje($mensaje, $casillaAuth->id);

        if (!$esDestinatario) {
            return response()->json(['error' => 'No autorizado'], Response::HTTP_FORBIDDEN);
        }

        $mensaje->update([
            'archivado' => !$mensaje->archivado,
        ]);

        return (new MensajeResource($mensaje->load('adjuntos', 'destinatarios')))->response();
    }

    /**
     * Actualiza un mensaje (reservado).
     *
     * Registro tecnico: se conserva implementacion para posible reactivacion futura.
     * Uso actual: deshabilitado por modelo unidireccional de notificaciones.
     */
    public function update(Request $request, Mensaje $mensaje)
    {
        return response()->json([
            'status' => 'error',
            'message' => 'Operacion reservada: la edicion de mensajes no esta habilitada',
        ], Response::HTTP_METHOD_NOT_ALLOWED);

        /*
        if (!$this->canWriteNotifications($request)) {
            return response()->json(['error' => 'No autorizado'], Response::HTTP_FORBIDDEN);
        }

        $casillaAuth = $this->casillaIdentity->getAuthCasilla($request);
        if (!$casillaAuth || $mensaje->casilla_origen_id !== $casillaAuth->id) {
            return response()->json(['error' => 'No autorizado'], Response::HTTP_FORBIDDEN);
        }

        $validator = Validator::make($request->all(), Mensaje::$validables + [
            'archivo_ids' => 'nullable|array',
            'archivo_ids.*' => 'integer|distinct',
            'normatividad_referencias' => 'nullable|array',
            'normatividad_referencias.*.normatividad_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            DB::beginTransaction();

            $validated = $validator->validated();
            $validated['casilla_origen_id'] = $casillaAuth->id;

            $archivoIds = $validated['archivo_ids'] ?? [];
            $sgdReferencias = $validated['sgd_referencias'] ?? [];
            $normatividadReferencias = $validated['normatividad_referencias'] ?? [];

            unset($validated['archivo_ids'], $validated['sgd_referencias'], $validated['normatividad_referencias']);

            // Valida casilla destino activa en caso de cambio de destinatario.
            $casillaDestino = Casilla::where('id', $validated['casilla_destino_id'])
                ->where('activo', true)
                ->where(function ($query) {
                    $query->whereNull('fecha_fin')
                        ->orWhereDate('fecha_fin', '>=', now()->toDateString());
                })
                ->first();

            if (!$casillaDestino) {
                DB::rollBack();

                return response()->json([
                    'status' => 'error',
                    'message' => 'La casilla destinataria no existe o no esta activa',
                ], Response::HTTP_BAD_REQUEST);
            }

            $mensaje->update($validated);

            if (
                $request->has('archivo_ids') ||
                $request->has('sgd_referencias') ||
                $request->has('normatividad_referencias')
            ) {
                DB::table('adjuntos')->where('mensaje_id', $mensaje->id)->delete();

                $rowsAdjuntos = [];

                foreach ($archivoIds as $archivoId) {
                    $rowsAdjuntos[] = [
                        'mensaje_id' => $mensaje->id,
                        'referencia_id' => $archivoId,
                        'tipo' => 'archivo',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                foreach ($sgdReferencias as $referencia) {
                    $rowsAdjuntos[] = [
                        'mensaje_id' => $mensaje->id,
                        'referencia_id' => $referencia['documento_id'],
                        'tipo' => 'documento_sgd',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                foreach ($normatividadReferencias as $referencia) {
                    $rowsAdjuntos[] = [
                        'mensaje_id' => $mensaje->id,
                        'referencia_id' => $referencia['normatividad_id'],
                        'tipo' => 'normatividad',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if (!empty($rowsAdjuntos)) {
                    DB::table('adjuntos')->insert($rowsAdjuntos);
                }
            }

            DB::commit();

            return new MensajeResource($mensaje->load('adjuntos'));
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Error al actualizar el mensaje: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        */
    }

    /**
     * Elimina un mensaje de forma logica (reservado).
     *
     * Registro tecnico: se conserva implementacion para posible reactivacion futura.
     * Uso actual: deshabilitado por modelo unidireccional de notificaciones.
     */
    public function destroy(Request $request, Mensaje $mensaje)
    {
        return response()->json([
            'status' => 'error',
            'message' => 'Operacion reservada: la eliminacion de mensajes no esta habilitada',
        ], Response::HTTP_METHOD_NOT_ALLOWED);

        /*
        if (!$this->canWriteNotifications($request)) {
            return response()->json(['error' => 'No autorizado'], Response::HTTP_FORBIDDEN);
        }

        $casillaAuth = $this->casillaIdentity->getAuthCasilla($request);
        if (!$casillaAuth || $mensaje->casilla_origen_id !== $casillaAuth->id) {
            return response()->json(['error' => 'No autorizado'], Response::HTTP_FORBIDDEN);
        }

        try {
            DB::beginTransaction();
            $mensaje->delete();
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Mensaje eliminado correctamente.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Error al eliminar el mensaje: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        */
    }

    /**
     * Genera un certificado en PDF para el mensaje.
     */
    public function generarCertificadoPdf(Request $request, Mensaje $mensaje)
    {
        // 1. Validar que el usuario tenga acceso a este mensaje (origen, o
        // destinatario -primario o secundario de un envio multiple-).
        $casillaAuth = $this->casillaIdentity->getAuthCasilla($request);
        if (!$casillaAuth) {
            return response()->json(['error' => 'No autorizado'], Response::HTTP_FORBIDDEN);
        }

        if (
            $mensaje->casilla_origen_id !== $casillaAuth->id
            && !$this->esDestinatarioDelMensaje($mensaje, $casillaAuth->id)
        ) {
            return response()->json(['error' => 'No autorizado'], Response::HTTP_FORBIDDEN);
        }

        // 2. Buscar el documento adjunto de tipo documento_sgd (para el nombre
        // de archivo, se necesita en cualquier caso).
        $adjuntoSgd = $mensaje->adjuntos()
            ->where('tipo', 'documento_sgd')
            ->first();

        // 3. El certificado cambia si el mensaje pasa de "pendiente de
        // lectura" a "leido" (una sola vez), pero es inmutable en adelante:
        // la clave de cache incluye ese estado para invalidarse justo cuando
        // corresponde y quedar fija despues.
        $estadoLectura = $mensaje->leido && $mensaje->read_at
            ? 'leido-' . $mensaje->read_at->timestamp
            : 'pendiente';
        $cacheKey = "certificado_{$mensaje->id}_{$estadoLectura}";

        $signedPdfContent = $this->obtenerPdfFirmadoCacheado($cacheKey, function () use ($request, $mensaje, $adjuntoSgd) {
            $token = $request->bearerToken() ?: $request->query('token');

            // Cargar Casillas
            $casillaOrigen = Casilla::find($mensaje->casilla_origen_id);
            $casillaDestino = Casilla::find($mensaje->casilla_destino_id);

            // Datos del remitente (cargo/dependencia congelados al momento del
            // envio) y del destinatario (solo persona: nombre + DNI, sin cargo).
            // Fallback a la designacion actual de la casilla origen para mensajes
            // creados antes de que existiera `designacion_origen_id`.
            $designacionRemitente = $mensaje->designacion_origen_id ?? $casillaOrigen?->designacion_id;
            $remitente = $this->casillaIdentity->fetchActorDetailsByDesignacionId($designacionRemitente, $token);
            $destinatario = $this->casillaIdentity->resolvePersonaBasica($casillaDestino, $token);

            // Detalles del documento SGD
            $documento = [
                'id' => $adjuntoSgd ? $adjuntoSgd->referencia_id : null,
                'asunto' => $mensaje->asunto,
                'fecha_envio' => $mensaje->created_at ? $mensaje->created_at->setTimezone('America/Lima')->format('d/m/Y H:i:s') : 'N/A',
                'fecha_lectura' => $mensaje->leido && $mensaje->read_at ? $mensaje->read_at->setTimezone('America/Lima')->format('d/m/Y H:i:s') : 'PENDIENTE DE LECTURA',
            ];

            // Generar el PDF usando DomPDF
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.certificado', [
                'mensaje' => $mensaje,
                'casillaOrigen' => $casillaOrigen,
                'casillaDestino' => $casillaDestino,
                'remitente' => $remitente,
                'destinatario' => $destinatario,
                'documento' => $documento,
                'hash' => sha1($mensaje->id . $mensaje->created_at)
            ]);

            return $pdf->output();
        }, 'Certificado de Transmisión y Lectura Electrónica');

        return response($signedPdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Certificado_Notificacion_' . ($adjuntoSgd ? $adjuntoSgd->referencia_id : $mensaje->id) . '.pdf"',
        ]);
    }

    /**
     * Genera la constancia de notificación electrónica (envío/depósito).
     */
    public function generarConstanciaEnvioPdf(Request $request, Mensaje $mensaje)
    {
        $casillaAuth = $this->casillaIdentity->getAuthCasilla($request);
        if (!$casillaAuth) {
            return response()->json(['error' => 'No autorizado'], Response::HTTP_FORBIDDEN);
        }

        if (
            $mensaje->casilla_origen_id !== $casillaAuth->id
            && !$this->esDestinatarioDelMensaje($mensaje, $casillaAuth->id)
        ) {
            return response()->json(['error' => 'No autorizado'], Response::HTTP_FORBIDDEN);
        }

        // El envio es un hecho fijo del pasado (fecha_envio = created_at): la
        // constancia nunca cambia una vez emitida, se puede cachear solo por
        // mensaje.
        $cacheKey = "constancia_envio_{$mensaje->id}";

        $signedPdfContent = $this->obtenerPdfFirmadoCacheado($cacheKey, function () use ($request, $mensaje) {
            $token = $request->bearerToken() ?: $request->query('token');

            $casillaOrigen = Casilla::find($mensaje->casilla_origen_id);
            $casillaDestino = Casilla::find($mensaje->casilla_destino_id);

            // Fallback a la designacion actual de la casilla origen para mensajes
            // creados antes de que existiera `designacion_origen_id`.
            $designacionRemitente = $mensaje->designacion_origen_id ?? $casillaOrigen?->designacion_id;
            $remitente = $this->casillaIdentity->fetchActorDetailsByDesignacionId($designacionRemitente, $token);
            $destinatario = $this->casillaIdentity->resolvePersonaBasica($casillaDestino, $token);

            $fecha_envio = $mensaje->created_at ? $mensaje->created_at->setTimezone('America/Lima')->format('d/m/Y H:i:s') : 'N/A';

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.constancia_envio', [
                'mensaje' => $mensaje,
                'casillaOrigen' => $casillaOrigen,
                'casillaDestino' => $casillaDestino,
                'remitente' => $remitente,
                'destinatario' => $destinatario,
                'fecha_envio' => $fecha_envio
            ]);

            return $pdf->output();
        }, 'Constancia de Envío de Notificación');

        return response($signedPdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Constancia_Envio_' . $mensaje->id . '.pdf"',
        ]);
    }

    /**
     * Genera la constancia de lectura de notificación electrónica.
     */
    public function generarConstanciaLecturaPdf(Request $request, Mensaje $mensaje)
    {
        $casillaAuth = $this->casillaIdentity->getAuthCasilla($request);
        if (!$casillaAuth) {
            return response()->json(['error' => 'No autorizado'], Response::HTTP_FORBIDDEN);
        }

        $esOrigen = $mensaje->casilla_origen_id === $casillaAuth->id;
        if (!$esOrigen && !$this->esDestinatarioDelMensaje($mensaje, $casillaAuth->id)) {
            return response()->json(['error' => 'No autorizado'], Response::HTTP_FORBIDDEN);
        }

        // La constancia de lectura es por destinatario: si quien la solicita es
        // un destinatario (primario o secundario), refleja SU propia lectura
        // (tabla pivote mensaje_destinatarios), no la de otro destinatario del
        // mismo envio multiple. Si quien la solicita es el origen (emisor),
        // refleja al destinatario primario (compatibilidad de flujos de un solo
        // destinatario, donde `mensajes.leido/read_at` sigue siendo el espejo).
        if ($esOrigen) {
            $leido = (bool) $mensaje->leido;
            $readAt = $mensaje->read_at;
            $casillaLectora = Casilla::find($mensaje->casilla_destino_id);
        } else {
            $destinatarioPivot = MensajeDestinatario::where('mensaje_id', $mensaje->id)
                ->where('casilla_id', $casillaAuth->id)
                ->first();
            $leido = (bool) ($destinatarioPivot->leido ?? false);
            $readAt = $destinatarioPivot->read_at ?? null;
            $casillaLectora = $casillaAuth;
        }

        if (!$leido || !$readAt) {
            return response()->json(['error' => 'El mensaje aun no ha sido leido por el destinatario'], Response::HTTP_BAD_REQUEST);
        }

        // La lectura de UN destinatario concreto ya ocurrio y no cambia: se
        // cachea por mensaje + casilla lectora (cada destinatario tiene su
        // propia constancia).
        $cacheKey = "constancia_lectura_{$mensaje->id}_{$casillaLectora->id}";

        $signedPdfContent = $this->obtenerPdfFirmadoCacheado($cacheKey, function () use ($request, $mensaje, $casillaLectora, $readAt) {
            $token = $request->bearerToken() ?: $request->query('token');

            $casillaOrigen = Casilla::find($mensaje->casilla_origen_id);

            // Fallback a la designacion actual de la casilla origen para mensajes
            // creados antes de que existiera `designacion_origen_id`.
            $designacionRemitente = $mensaje->designacion_origen_id ?? $casillaOrigen?->designacion_id;
            $remitente = $this->casillaIdentity->fetchActorDetailsByDesignacionId($designacionRemitente, $token);
            $destinatario = $this->casillaIdentity->resolvePersonaBasica($casillaLectora, $token);

            $fecha_lectura = $readAt->setTimezone('America/Lima')->format('d/m/Y H:i:s');

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.constancia_lectura', [
                'mensaje' => $mensaje,
                'casillaOrigen' => $casillaOrigen,
                'casillaDestino' => $casillaLectora,
                'remitente' => $remitente,
                'destinatario' => $destinatario,
                'fecha_lectura' => $fecha_lectura
            ]);

            return $pdf->output();
        }, 'Constancia de Lectura de Notificación');

        return response($signedPdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Constancia_Lectura_' . $mensaje->id . '.pdf"',
        ]);
    }


    /**
     * Certificado/constancias son documentos inmutables una vez emitidos: el
     * render con DomPDF + la firma digital criptográfica (parseo de PFX y
     * reconstrucción página por página con TCPDF/FPDI) son costosos y, sin
     * caché, se repetían enteros cada vez que alguien volvía a abrir el mismo
     * PDF. Aquí se persiste el PDF ya firmado en disco (clave = tipo de
     * documento + mensaje + estado relevante) y, si ya existe, se sirve
     * directo sin tocar Auth Service ni volver a firmar. `$generarPdfSinFirmar`
     * solo se invoca en caso de cache-miss, para no pagar tampoco el costo de
     * resolver remitente/destinatario cuando no hace falta.
     */
    private function obtenerPdfFirmadoCacheado(string $cacheKey, callable $generarPdfSinFirmar, string $reason): string
    {
        $disk = Storage::disk('local');
        $path = 'pdfs-firmados/' . $cacheKey . '.pdf';

        if ($disk->exists($path)) {
            return $disk->get($path);
        }

        $signedPdfContent = $this->firmarPdf($generarPdfSinFirmar(), $reason);
        $disk->put($path, $signedPdfContent);

        return $signedPdfContent;
    }

    /**
     * Firma digitalmente el contenido de un PDF usando el certificado PFX configurado.
     */
    private function firmarPdf(string $pdfRawContent, string $reason): string
    {
        $pfxPath = env('DIGITAL_SIGNATURE_PFX_PATH');
        $password = env('DIGITAL_SIGNATURE_PFX_PASSWORD');

        if (!$pfxPath) {
            \Log::warning("Firma digital omitida: DIGITAL_SIGNATURE_PFX_PATH no configurado.");
            return $pdfRawContent;
        }

        $absolutePfxPath = base_path($pfxPath);
        if (!file_exists($absolutePfxPath)) {
            $absolutePfxPath = $pfxPath;
            if (!file_exists($absolutePfxPath)) {
                \Log::warning("No se encontró el certificado de firma digital en: " . $pfxPath);
                return $pdfRawContent;
            }
        }

        $certs = [];
        if (!openssl_pkcs12_read(file_get_contents($absolutePfxPath), $certs, $password)) {
            \Log::error("No se pudo leer el certificado digital PFX para la firma.");
            return $pdfRawContent;
        }

        $privateKey = $certs['pkey'];
        $publicCert = $certs['cert'];

        $tempInput = tempnam(sys_get_temp_dir(), 'pdf_in');
        file_put_contents($tempInput, $pdfRawContent);

        $tempOutput = tempnam(sys_get_temp_dir(), 'pdf_out');

        try {
            $pdf = new \setasign\Fpdi\Tcpdf\Fpdi();
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);

            $pageCount = $pdf->setSourceFile($tempInput);

            $info = [
                'Name' => 'GRH Casilla Electrónica',
                'Location' => 'Lima, Perú',
                'Reason' => $reason,
                'ContactInfo' => 'soporte@grh.gob.pe',
            ];

            // Aplicar firma digital criptográfica PKCS#7
            $pdf->setSignature($publicCert, $privateKey, $password, '', 1, $info);

            $pdf->SetAutoPageBreak(false);

            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);

                // Estampar firma visual en la última página
                if ($pageNo === $pageCount) {
                    $w = 72;
                    $h = 24;
                    $x = $size['width'] - $w - 15; // 15mm de margen derecho
                    $y = 15; // 15mm de margen superior (esquina superior derecha)

                    // 1. Establecer la posición de la firma digital invisible/interactiva en el lector de PDF
                    $pdf->setSignatureAppearance($x, $y, $w, $h);

                    // 2. Dibujar el logo del Gobierno Regional a la izquierda de la firma si existe
                    $logoPath = public_path('media/logo-oficial.png');
                    if (file_exists($logoPath)) {
                        $pdf->Image($logoPath, $x + 1, $y + 3.5, 17, 17);
                    }

                    // Dibujar una línea vertical divisoria sutil
                    $pdf->SetLineStyle(array('width' => 0.1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(200, 200, 200)));
                    $pdf->Line($x + 19.5, $y + 2.5, $x + 19.5, $y + 21.5);

                    // Escribir el texto de la firma con tipografía adecuada
                    $pdf->SetTextColor(30, 30, 30);
                    
                    // Título
                    $pdf->SetFont('helvetica', 'B', 6.5);
                    $pdf->SetXY($x + 21, $y + 2.5);
                    $pdf->Cell($w - 22, 3, 'FIRMADO DIGITALMENTE', 0, 1, 'L');

                    // Detalles
                    $pdf->SetFont('helvetica', '', 5.5);
                    $pdf->SetXY($x + 21, $y + 6);
                    $pdf->Cell($w - 22, 3.2, 'Entidad: GOBIERNO REGIONAL DE HUÁNUCO', 0, 1, 'L');
                    
                    $pdf->SetXY($x + 21, $y + 9.5);
                    
                    // Limitar tamaño de razón si es muy larga
                    $shortReason = strlen($reason) > 38 ? substr($reason, 0, 35) . '...' : $reason;
                    $pdf->Cell($w - 22, 3.2, 'Motivo: ' . $shortReason, 0, 1, 'L');
                    
                    $pdf->SetXY($x + 21, $y + 13);
                    $pdf->Cell($w - 22, 3.2, 'Fecha: ' . now()->setTimezone('America/Lima')->format('d/m/Y H:i:s'), 0, 1, 'L');

                    $pdf->SetXY($x + 21, $y + 16.5);
                    $pdf->Cell($w - 22, 3.2, 'Validador: sgd.grh.gob.pe/validador', 0, 1, 'L');

                    // Restaurar propiedades de dibujo por defecto
                    $pdf->SetTextColor(0, 0, 0);
                }
            }

            $pdf->Output($tempOutput, 'F');
            $signedContent = file_get_contents($tempOutput);

            @unlink($tempInput);
            @unlink($tempOutput);

            return $signedContent;
        } catch (\Exception $e) {
            \Log::error("Error al firmar digitalmente el PDF: " . $e->getMessage());
            @unlink($tempInput);
            @unlink($tempOutput);
            return $pdfRawContent;
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Resources\MensajeResource;
use App\Models\Casilla;
use App\Models\Mensaje;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * MensajeController.
 *
 * Gestiona bandejas y operaciones de mensajes entre casillas.
 * La autorizacion por permisos/roles se resuelve en frontend.
 */
class MensajeController extends Controller
{
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
                ->with('adjuntos')
                ->filter($request)
                ->orderByDesc('created_at')
                ->paginate($this->resolvePerPage($request))
        );
    }

    /**
     * Obtiene datos de usuario autenticado inyectados por RemoteAuth.
     */
    private function getAuthUser(Request $request): array
    {
        $authUser = $request->input('auth_user', []);

        return is_array($authUser) ? $authUser : [];
    }

    /**
     * Determina si el usuario autenticado puede emitir notificaciones.
     */
    private function canWriteNotifications(Request $request): bool
    {
        $authUser = $this->getAuthUser($request);

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
     * Resuelve la casilla activa para una designacion.
     */
    private function getActiveCasillaByDesignacionId(int $designacionId): ?Casilla
    {
        return Casilla::where('designacion_id', $designacionId)
            ->where('activo', true)
            ->where(function ($query) {
                $query->whereNull('fecha_fin')
                    ->orWhereDate('fecha_fin', '>=', now()->toDateString());
            })
            ->first();
    }

    /**
     * Resuelve la casilla activa de la designacion autenticada.
     */
    private function getAuthCasilla(Request $request): ?Casilla
    {
        $designacionId = $this->getAuthDesignacionId($request);
        if (!$designacionId) {
            return null;
        }

        return $this->getActiveCasillaByDesignacionId($designacionId);
    }

    /**
     * Resuelve el ID de designacion activa desde el payload de auth.
     */
    private function getAuthDesignacionId(Request $request): ?int
    {
        $authUser = $this->getAuthUser($request);

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

    /**
    * Bandeja de entrada por casilla destino.
     *
     * Regla B-02: paginacion obligatoria con per_page=10 por defecto, maximo 100.
     */
    public function bandejaEntrada(Request $request)
    {
        $casillaAuth = $this->getAuthCasilla($request);
        if (!$casillaAuth) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se pudo resolver una casilla activa para la designacion autenticada',
            ], Response::HTTP_FORBIDDEN);
        }

        return $this->paginateMensajes(
            $request,
            Mensaje::where('casilla_destino_id', $casillaAuth->id)
                ->where('archivado', false)
        );
    }

    /**
    * Bandeja de mensajes destacados recibidos.
     */
    public function bandejaDestacados(Request $request)
    {
        $casillaAuth = $this->getAuthCasilla($request);
        if (!$casillaAuth) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se pudo resolver una casilla activa para la designacion autenticada',
            ], Response::HTTP_FORBIDDEN);
        }

        return $this->paginateMensajes(
            $request,
            Mensaje::where('casilla_destino_id', $casillaAuth->id)
                ->where('destacado', true)
                ->where('archivado', false)
        );
    }

    /**
    * Bandeja de mensajes archivados recibidos.
     */
    public function bandejaArchivados(Request $request)
    {
        $casillaAuth = $this->getAuthCasilla($request);
        if (!$casillaAuth) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se pudo resolver una casilla activa para la designacion autenticada',
            ], Response::HTTP_FORBIDDEN);
        }

        return $this->paginateMensajes(
            $request,
            Mensaje::where('casilla_destino_id', $casillaAuth->id)
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
        $casillaAuth = $this->getAuthCasilla($request);
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
     * Muestra un mensaje si pertenece a la casilla origen o destino autenticada.
     */
    public function show(Request $request, Mensaje $mensaje)
    {
        $casillaAuth = $this->getAuthCasilla($request);
        if (!$casillaAuth) {
            return response()->json(['error' => 'No autorizado'], Response::HTTP_FORBIDDEN);
        }

        if (
            $mensaje->casilla_origen_id !== $casillaAuth->id &&
            $mensaje->casilla_destino_id !== $casillaAuth->id
        ) {
            return response()->json(['error' => 'No autorizado'], Response::HTTP_FORBIDDEN);
        }

        return new MensajeResource($mensaje->load('adjuntos'));
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
        if (!$this->canWriteNotifications($request)) {
            return response()->json([
                'status' => 'error',
                'message' => 'No autorizado para enviar notificaciones',
            ], Response::HTTP_FORBIDDEN);
        }

        $casillaAuth = $this->getAuthCasilla($request);
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

        $archivoIds = $validated['archivo_ids'] ?? [];
        $sgdReferencias = $validated['sgd_referencias'] ?? [];
        $normatividadReferencias = $validated['normatividad_referencias'] ?? [];

        unset($validated['archivo_ids'], $validated['sgd_referencias'], $validated['normatividad_referencias']);

        // Valida que la casilla destino exista y este activa.
        $casillaDestino = Casilla::where('id', $validated['casilla_destino_id'])
            ->where('activo', true)
            ->where(function ($query) {
                $query->whereNull('fecha_fin')
                    ->orWhereDate('fecha_fin', '>=', now()->toDateString());
            })
            ->first();

        if (!$casillaDestino) {
            return response()->json([
                'status' => 'error',
                'message' => 'La casilla destinataria no existe o no esta activa',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            DB::beginTransaction();

            $mensaje = Mensaje::create($validated);
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

            return (new MensajeResource($mensaje->load('adjuntos')))
                ->response()
                ->setStatusCode(Response::HTTP_CREATED);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Error al guardar el mensaje: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Marca un mensaje como leido.
     * Solo la casilla destino puede marcar lectura.
     */
    public function marcarLeido(Request $request, Mensaje $mensaje)
    {
        $casillaAuth = $this->getAuthCasilla($request);
        if (!$casillaAuth || $casillaAuth->id !== $mensaje->casilla_destino_id) {
            return response()->json(['error' => 'No autorizado'], Response::HTTP_FORBIDDEN);
        }

        $mensaje->update([
            'leido' => true,
            'read_at' => now(),
        ]);

        return (new MensajeResource($mensaje->load('adjuntos')))->response();
    }

    /**
     * Alterna el estado destacado del mensaje.
     */
    public function toggleDestacado(Request $request, Mensaje $mensaje)
    {
        $casillaAuth = $this->getAuthCasilla($request);
        if (!$casillaAuth || $casillaAuth->id !== $mensaje->casilla_destino_id) {
            return response()->json(['error' => 'No autorizado'], Response::HTTP_FORBIDDEN);
        }

        $mensaje->update([
            'destacado' => !$mensaje->destacado,
        ]);

        return (new MensajeResource($mensaje->load('adjuntos')))->response();
    }

    /**
     * Alterna el estado archivado del mensaje.
     */
    public function toggleArchivado(Request $request, Mensaje $mensaje)
    {
        $casillaAuth = $this->getAuthCasilla($request);
        if (!$casillaAuth || $casillaAuth->id !== $mensaje->casilla_destino_id) {
            return response()->json(['error' => 'No autorizado'], Response::HTTP_FORBIDDEN);
        }

        $mensaje->update([
            'archivado' => !$mensaje->archivado,
        ]);

        return (new MensajeResource($mensaje->load('adjuntos')))->response();
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

        $casillaAuth = $this->getAuthCasilla($request);
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

        $casillaAuth = $this->getAuthCasilla($request);
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
}

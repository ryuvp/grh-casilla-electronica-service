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
        if ($request->filled('sgd_referencias')) {
            return true;
        }

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
     * Verifica masivamente el envío y lectura de documentos del SGD.
     */
    public function verificarEnvios(Request $request)
    {
        $documentoIds = $request->input('documento_ids', []);
        if (!is_array($documentoIds) || empty($documentoIds)) {
            return response()->json([]);
        }

        $mensajes = Mensaje::whereHas('adjuntos', function ($query) use ($documentoIds) {
            $query->where('tipo', 'documento_sgd')
                ->whereIn('referencia_id', $documentoIds);
        })
        ->with(['adjuntos'])
        ->get();

        $mapping = [];
        foreach ($mensajes as $mensaje) {
            $adjunto = $mensaje->adjuntos
                ->where('tipo', 'documento_sgd')
                ->first();

            if ($adjunto) {
                $docId = $adjunto->referencia_id;
                $mapping[$docId] = [
                    'enviado' => true,
                    'mensaje_id' => $mensaje->id,
                    'leido' => (bool) $mensaje->leido,
                    'fecha_envio' => $mensaje->created_at ? $mensaje->created_at->toDateTimeString() : null,
                    'fecha_lectura' => $mensaje->read_at ? $mensaje->read_at->toDateTimeString() : null,
                    'casilla_origen_id' => $mensaje->casilla_origen_id,
                    'casilla_destino_id' => $mensaje->casilla_destino_id,
                ];
            }
        }

        foreach ($documentoIds as $id) {
            if (!isset($mapping[$id])) {
                $mapping[$id] = null;
            }
        }

        return response()->json($mapping);
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
        if (!$request->filled('casilla_destino_id') && $request->filled('designacion_destino_id')) {
            $destDesignacionId = (int) $request->input('designacion_destino_id');
            $casillaDestino = Casilla::where('designacion_id', $destDesignacionId)
                ->where('activo', true)
                ->first();

            if (!$casillaDestino) {
                try {
                    $casillaDestino = Casilla::create([
                        'designacion_id' => $destDesignacionId,
                        'numero'         => 'CAS-' . $destDesignacionId,
                        'activo'         => true,
                        'fecha_inicio'   => now()->toDateString(),
                    ]);
                } catch (\Exception $e) {
                    \Log::error("Error auto-creando casilla destino para designación {$destDesignacionId} en store: " . $e->getMessage());
                }
            }

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

    /**
     * Genera un certificado en PDF para el mensaje.
     */
    public function generarCertificadoPdf(Request $request, Mensaje $mensaje)
    {
        // 1. Validar que el usuario tenga acceso a este mensaje
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

        $token = $request->bearerToken() ?: $request->query('token');

        // 2. Cargar Casillas
        $casillaOrigen = Casilla::find($mensaje->casilla_origen_id);
        $casillaDestino = Casilla::find($mensaje->casilla_destino_id);

        // 3. Obtener detalles del remitente y destinatario del Auth Service
        $remitente = [];
        $destinatario = [];
        if ($casillaOrigen) {
            $remitente = $this->fetchActorDetailsByDesignacionId($casillaOrigen->designacion_id, $token);
        }
        if ($casillaDestino) {
            $destinatario = $this->fetchActorDetailsByDesignacionId($casillaDestino->designacion_id, $token);
        }

        // 4. Buscar el documento adjunto de tipo documento_sgd
        $adjuntoSgd = $mensaje->adjuntos()
            ->where('tipo', 'documento_sgd')
            ->first();

        // 5. Cargar detalles del documento SGD
        $documento = [
            'id' => $adjuntoSgd ? $adjuntoSgd->referencia_id : null,
            'asunto' => $mensaje->asunto,
            'fecha_envio' => $mensaje->created_at ? $mensaje->created_at->setTimezone('America/Lima')->format('d/m/Y H:i:s') : 'N/A',
            'fecha_lectura' => $mensaje->leido && $mensaje->read_at ? $mensaje->read_at->setTimezone('America/Lima')->format('d/m/Y H:i:s') : 'PENDIENTE DE LECTURA',
        ];

        // 6. Generar el PDF usando DomPDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.certificado', [
            'mensaje' => $mensaje,
            'casillaOrigen' => $casillaOrigen,
            'casillaDestino' => $casillaDestino,
            'remitente' => $remitente,
            'destinatario' => $destinatario,
            'documento' => $documento,
            'hash' => sha1($mensaje->id . $mensaje->created_at)
        ]);

        return $pdf->stream('Certificado_Notificacion_' . ($documento['id'] ?: $mensaje->id) . '.pdf');
    }

    /**
     * Genera la constancia de notificación electrónica (envío/depósito).
     */
    public function generarConstanciaEnvioPdf(Request $request, Mensaje $mensaje)
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

        $token = $request->bearerToken() ?: $request->query('token');

        $casillaOrigen = Casilla::find($mensaje->casilla_origen_id);
        $casillaDestino = Casilla::find($mensaje->casilla_destino_id);

        $remitente = [];
        $destinatario = [];
        if ($casillaOrigen) {
            $remitente = $this->fetchActorDetailsByDesignacionId($casillaOrigen->designacion_id, $token);
        }
        if ($casillaDestino) {
            $destinatario = $this->fetchActorDetailsByDesignacionId($casillaDestino->designacion_id, $token);
        }

        $fecha_envio = $mensaje->created_at ? $mensaje->created_at->setTimezone('America/Lima')->format('d/m/Y H:i:s') : 'N/A';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.constancia_envio', [
            'mensaje' => $mensaje,
            'casillaOrigen' => $casillaOrigen,
            'casillaDestino' => $casillaDestino,
            'remitente' => $remitente,
            'destinatario' => $destinatario,
            'fecha_envio' => $fecha_envio
        ]);

        return $pdf->stream('Constancia_Envio_' . $mensaje->id . '.pdf');
    }

    /**
     * Genera la constancia de lectura de notificación electrónica.
     */
    public function generarConstanciaLecturaPdf(Request $request, Mensaje $mensaje)
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

        if (!$mensaje->leido || !$mensaje->read_at) {
            return response()->json(['error' => 'El mensaje aun no ha sido leido por el destinatario'], Response::HTTP_BAD_REQUEST);
        }

        $token = $request->bearerToken() ?: $request->query('token');

        $casillaOrigen = Casilla::find($mensaje->casilla_origen_id);
        $casillaDestino = Casilla::find($mensaje->casilla_destino_id);

        $remitente = [];
        $destinatario = [];
        if ($casillaOrigen) {
            $remitente = $this->fetchActorDetailsByDesignacionId($casillaOrigen->designacion_id, $token);
        }
        if ($casillaDestino) {
            $destinatario = $this->fetchActorDetailsByDesignacionId($casillaDestino->designacion_id, $token);
        }

        $fecha_lectura = $mensaje->read_at->setTimezone('America/Lima')->format('d/m/Y H:i:s');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.constancia_lectura', [
            'mensaje' => $mensaje,
            'casillaOrigen' => $casillaOrigen,
            'casillaDestino' => $casillaDestino,
            'remitente' => $remitente,
            'destinatario' => $destinatario,
            'fecha_lectura' => $fecha_lectura
        ]);

        return $pdf->stream('Constancia_Lectura_' . $mensaje->id . '.pdf');
    }


    /**
     * Obtiene los detalles de la designación desde el Auth Service.
     */
    private function fetchActorDetailsByDesignacionId(int $designacionId, string $token): array
    {
        $url = env('AUTH_SERVICE_URL') . '/api/designaciones/' . $designacionId . '/usuario-cargo';
        try {
            $response = \Illuminate\Support\Facades\Http::withToken($token)->get($url);
            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            \Log::error("Error fetching actor details for designacion {$designacionId}: " . $e->getMessage());
        }

        return [];
    }
}

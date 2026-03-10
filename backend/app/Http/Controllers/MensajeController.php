<?php

namespace App\Http\Controllers;

use App\Models\Mensaje;
use App\Http\Resources\MensajeResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;

/**
 * MensajeController
 *
 * Gestiona mensajes de casillas electrónicas.
 * Nota: la autorización por permisos/roles se gestiona en frontend.
 * En backend se valida token y reglas de negocio de datos.
 */
class MensajeController extends Controller
{
    /**
     * Obtiene datos de usuario autenticado inyectados por RemoteAuth.
     */
    private function getAuthUser(Request $request): array
    {
        $authUser = $request->input('auth_user', []);

        // Fuerza estructura segura para evitar errores de tipo.
        return is_array($authUser) ? $authUser : [];
    }

    /**
     * Bandeja de entrada: mensajes recibidos.
     * Retorna todos los mensajes donde el usuario autenticado es el destinatario.
     * 
     * Regla B-02: Paginación obligatoria con per_page=10 por defecto, máximo 100.
     */
    public function bandejaEntrada(Request $request)
    {
        // Obtiene el usuario autenticado inyectado por middleware remoto.
        $authUser = $this->getAuthUser($request);
        
        // B-02: Paginación obligatoria
        $perPage = (int) ($request->per_page ?? 10);
        $perPage = min($perPage, 100);
        
        $mensajes = Mensaje::where('usuario_destino_id', $authUser['id'])
            ->with('adjuntos')
            ->filter($request)
            ->orderByDesc('fecha_envio')
            ->paginate($perPage);

        return MensajeResource::collection($mensajes);
    }

    /**
     * Bandeja de salida: mensajes enviados.
     * Retorna todos los mensajes donde el usuario autenticado es el remitente.
     * 
     * Regla B-02: Paginación obligatoria con per_page=10 por defecto, máximo 100.
     */
    public function bandejaEnviados(Request $request)
    {
        // Obtiene el usuario autenticado inyectado por middleware remoto.
        $authUser = $this->getAuthUser($request);
        
        // B-02: Paginación obligatoria
        $perPage = (int) ($request->per_page ?? 10);
        $perPage = min($perPage, 100);
        
        $mensajes = Mensaje::where('usuario_origen_id', $authUser['id'])
            ->with('adjuntos')
            ->filter($request)
            ->orderByDesc('fecha_envio')
            ->paginate($perPage);

        return MensajeResource::collection($mensajes);
    }

    /**
     * Mostrar mensaje específico (si pertenece al usuario).
     * El usuario autenticado sólo puede ver mensajes donde es origen o destino.
     */
    public function show(Request $request, Mensaje $mensaje)
    {
        $authUser = $this->getAuthUser($request);

        // Restringe acceso al mensaje solo a participantes directos.
        if (
            $mensaje->usuario_origen_id !== $authUser['id'] &&
            $mensaje->usuario_destino_id !== $authUser['id']
        ) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        return new MensajeResource($mensaje->load('adjuntos'));
    }

    /**
     * Guardar un nuevo mensaje y sus archivos adjuntos.
     * El usuario autenticado será el remitente (usuario_origen_id).
     * 
     * Regla B-05: Validar que el destinatario tenga una casilla activa antes de enviar.
     *
     * Persistencia de referencias externas:
     * - tipo=archivo -> referencia a File Service
     * - tipo=documento_sgd -> referencia a SGD
     * - tipo=normatividad -> referencia normativa
     */
    public function store(Request $request)
    {
        $authUser = $this->getAuthUser($request);

        $validator = Validator::make($request->all(), Mensaje::$validables + [
            'archivo_ids' => 'nullable|array',
            'archivo_ids.*' => 'integer|distinct',
            'normatividad_referencias' => 'nullable|array',
            'normatividad_referencias.*.normatividad_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], Response::HTTP_BAD_REQUEST);
        }

        $validated = $validator->validated();
        $validated['usuario_origen_id'] = $authUser['id'];
        $validated['fecha_envio'] = now();

        // Separa referencias externas para persistirlas en la tabla adjuntos.
        $archivoIds = $validated['archivo_ids'] ?? [];
        $sgdReferencias = $validated['sgd_referencias'] ?? [];
        $normatividadReferencias = $validated['normatividad_referencias'] ?? [];

        unset($validated['archivo_ids'], $validated['sgd_referencias'], $validated['normatividad_referencias']);

        // B-05: valida que el destinatario tenga casilla activa antes de enviar.
        $casillaDestino = \App\Models\Casilla::where('titular_id', $validated['usuario_destino_id'])
            ->where('titular_tipo', 1) // 1 = Usuario
            ->where('activo', true)
            ->whereNull('fecha_fin') // Sin fecha de vencimiento o fecha futura
            ->orWhere(function($query) {
                $query->whereDate('fecha_fin', '>=', now()->toDateString());
            })
            ->first();

        if (!$casillaDestino) {
            return response()->json([
                'status' => 'error',
                'message' => 'El usuario destinatario no tiene una casilla electrónica activa'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Inicia transaccion para garantizar consistencia mensaje + adjuntos.
            DB::beginTransaction();

            $mensaje = Mensaje::create($validated);

            $rowsAdjuntos = [];

            // Mapea referencias de archivos digitales.
            foreach ($archivoIds as $archivoId) {
                $rowsAdjuntos[] = [
                    'mensaje_id' => $mensaje->id,
                    'referencia_id' => $archivoId,
                    'tipo' => 'archivo',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Mapea referencias de documentos SGD.
            foreach ($sgdReferencias as $referencia) {
                $rowsAdjuntos[] = [
                    'mensaje_id' => $mensaje->id,
                    'referencia_id' => $referencia['documento_id'],
                    'tipo' => 'documento_sgd',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Mapea referencias de normatividad.
            foreach ($normatividadReferencias as $referencia) {
                $rowsAdjuntos[] = [
                    'mensaje_id' => $mensaje->id,
                    'referencia_id' => $referencia['normatividad_id'],
                    'tipo' => 'normatividad',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Inserta lote de referencias externas solo si hay datos.
            if (!empty($rowsAdjuntos)) {
                DB::table('adjuntos')->insert($rowsAdjuntos);
            }

            DB::commit();

            return new MensajeResource($mensaje->load('adjuntos'));
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Error al guardar el mensaje: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * Marcar un mensaje como leído.
     * Sólo el destinatario puede marcar un mensaje como leído.
     */
    public function marcarLeido(Request $request, Mensaje $mensaje)
    {
        $authUser = $this->getAuthUser($request);
        
        if ($authUser['id'] !== $mensaje->usuario_destino_id) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        // Marca estado de lectura y fecha para trazabilidad.
        $mensaje->update([
            'leido' => true,
            'fecha_leido' => now()
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $mensaje
        ]);
    }

    /**
     * Actualizar un mensaje (sólo si es el remitente).
     * Sólo el usuario que envió el mensaje puede actualizarlo.
        *
        * Si el request incluye colecciones de referencias, se sincroniza por completo
        * la tabla adjuntos del mensaje.
     */
    public function update(Request $request, Mensaje $mensaje)
    {
        $authUser = $this->getAuthUser($request);
        
        if ($mensaje->usuario_origen_id !== $authUser['id']) {
            return response()->json(['error' => 'No autorizado'], 403);
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
                'errors' => $validator->errors()
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Inicia transaccion para garantizar consistencia de update + adjuntos.
            DB::beginTransaction();

            $validated = $validator->validated();

            $archivoIds = $validated['archivo_ids'] ?? [];
            $sgdReferencias = $validated['sgd_referencias'] ?? [];
            $normatividadReferencias = $validated['normatividad_referencias'] ?? [];

            unset($validated['archivo_ids'], $validated['sgd_referencias'], $validated['normatividad_referencias']);

            $mensaje->update($validated);

            // Si llega cualquier coleccion de referencias, se sincroniza adjuntos completos.
            if (
                $request->has('archivo_ids') ||
                $request->has('sgd_referencias') ||
                $request->has('normatividad_referencias')
            ) {
                // Limpia referencias anteriores para reconstruir estado final.
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
                'message' => 'Error al actualizar el mensaje: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Eliminar un mensaje.
     * Sólo el remitente puede eliminar el mensaje.
        *
        * Se aplica soft delete para mantener trazabilidad histórica.
     */
    public function destroy(Request $request, Mensaje $mensaje)
    {
        $authUser = $this->getAuthUser($request);
        
        if ($mensaje->usuario_origen_id !== $authUser['id']) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        try {
            DB::beginTransaction();
            $mensaje->delete();
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Mensaje eliminado correctamente.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Error al eliminar el mensaje: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

<?php

namespace App\Http\Controllers;

use RemoteAuth;
use App\Models\Mensaje;
use App\Http\Resources\MensajeResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;

class MensajeController extends Controller
{
    protected $user;

    public function __construct()
    {
        // Obtener el usuario autenticado
    }

    /**
     * Bandeja de entrada: mensajes recibidos.
     */
    public function bandejaEntrada(Request $request)
    {
        $mensajes = Mensaje::where('usuario_destino_id', $request->user['id'])
            ->with('adjuntos')
            ->filter($request)
            ->get();

        return MensajeResource::collection($mensajes);
    }

    /**
     * Bandeja de salida: mensajes enviados.
     */
    public function bandejaEnviados(Request $request)
    {
        $mensajes = Mensaje::where('usuario_origen_id', $this->user['id'])
            ->with('adjuntos')
            ->filter($request)
            ->get();

        return MensajeResource::collection($mensajes);
    }

    /**
     * Mostrar mensaje específico (si pertenece al usuario).
     */
    public function show(Mensaje $mensaje)
    {
        if (
            $mensaje->usuario_origen_id !== $this->user['id'] &&
            $mensaje->usuario_destino_id !== $this->user['id']
        ) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        return new MensajeResource($mensaje->load('adjuntos'));
    }

    /**
     * Guardar un nuevo mensaje y sus archivos adjuntos.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), Mensaje::$validables + [
            'archivo_ids' => 'nullable|array',
            'archivo_ids.*' => 'integer|distinct'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], Response::HTTP_BAD_REQUEST);
        }

        $validated = $validator->validated();
        $validated['usuario_origen_id'] = $this->user['id'];
        $validated['fecha_envio'] = now();

        try {
            DB::beginTransaction();

            $mensaje = Mensaje::create($validated);

            // Si existen archivos, guardarlos en la tabla adjuntos
            if (!empty($validated['archivo_ids'])) {
                $idsArchivos = collect($validated['archivo_ids'])
                    ->map(fn($archivo_id) => [
                        'mensaje_id' => $mensaje->id,
                        'archivo_id' => $archivo_id,
                    ])->toArray();

                DB::table('adjuntos')->insert($idsArchivos);
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
     */
    public function marcarLeido(Request $request, Mensaje $mensaje)
    {
        if ($this->user['id'] !== $mensaje->usuario_destino_id) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

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
     */
    public function update(Request $request, Mensaje $mensaje)
    {
        if ($mensaje->usuario_origen_id !== $this->user['id']) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $validator = Validator::make($request->all(), Mensaje::$validables);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            DB::beginTransaction();
            $mensaje->update($validator->validated());
            DB::commit();

            return new MensajeResource($mensaje->load('archivos'));
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
     */
    public function destroy(Mensaje $mensaje)
    {
        if ($mensaje->usuario_origen_id !== $this->user['id']) {
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
    private function consultarUsuarios(array $ids, Request $request)
    {
        if (empty($ids)) return [];

        try {
            $queryParams = [
                'searchField' => 'id',
                'search' => $ids,
            ];

            $token = $request->bearerToken();
            $response = Http::withToken($token)
                ->get(config('services.auth.url') . '/api/usuarios', $queryParams);

            return $response->ok() ? $response->json('data') : [];
        } catch (\Exception $e) {
            //\Log::error('Error al obtener información de usuarios: ' . $e->getMessage());
            return [];
        }
    }
}

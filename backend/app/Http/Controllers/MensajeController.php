<?php

namespace App\Http\Controllers;

use RemoteAuth;
use App\Models\Mensaje;
use App\Http\Resources\MensajeResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;

class MensajeController extends Controller
{
    /**
     * Bandeja de entrada: mensajes recibidos por el usuario autenticado.
     */
    public function bandejaEntrada(Request $request)
    {
        $user = RemoteAuth::user();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Usuario no autenticado'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $mensajes = Mensaje::where('usuario_destino_id', $user['id'])
            ->with('archivos')
            ->filter($request)
            ->get();

        return MensajeResource::collection($mensajes);
    }

    public function bandejaEnviados(Request $request)
    {
        $user = RemoteAuth::user();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Usuario no autenticado'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $mensajes = Mensaje::where('usuario_origen_id', $user['id'])
            ->with('archivos')
            ->filter($request)
            ->get();

        return MensajeResource::collection($mensajes);
    }

    public function show(Mensaje $mensaje)
    {
        $user = RemoteAuth::user();
        if (!$user || ($mensaje->usuario_origen_id !== $user['id'] && $mensaje->usuario_destino_id !== $user['id'])) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        return new MensajeResource($mensaje->load('archivos'));
    }


    /**
     * Almacena un nuevo mensaje.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), Mensaje::$validables + [
            'archivo_ids'   => 'nullable|array',
            'archivo_ids.*' => 'exists:archivos,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], Response::HTTP_BAD_REQUEST);
        }

        $validated = $validator->validated();

        $user = RemoteAuth::user();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Usuario no autenticado'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $validated['usuario_origen_id'] = $user['id'];
        $validated['fecha_envio'] = now();

        try {
            DB::beginTransaction();

            $mensaje = Mensaje::create($validated);

            if (!empty($validated['archivo_ids'])) {
                $mensaje->archivos()->attach($validated['archivo_ids']);
            }

            DB::commit();

            return new MensajeResource($mensaje->load('archivos'));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Error al crear el mensaje: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Marcar como leído.
     */
    public function marcarLeido(Request $request, Mensaje $mensaje)
    {
        $user = RemoteAuth::user();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Usuario no autenticado'
            ], Response::HTTP_UNAUTHORIZED);
        }

        if ($user['id'] !== $mensaje->usuario_destino_id) {
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
     * Actualiza un mensaje.
     */
    public function update(Request $request, Mensaje $mensaje)
    {
        $user = RemoteAuth::user();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Usuario no autenticado'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Verifica si el mensaje es del usuario autenticado
        if ($mensaje->usuario_origen_id !== $user['id']) {
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
            return new MensajeResource($mensaje);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Error al actualizar el mensaje: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Elimina un mensaje.
     */
    public function destroy(Mensaje $mensaje)
    {
        $user = RemoteAuth::user();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Usuario no autenticado'
            ], Response::HTTP_UNAUTHORIZED);
        }

        if ($mensaje->usuario_origen_id !== $user['id']) {
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

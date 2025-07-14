<?php

namespace App\Http\Controllers;

use App\Models\Archivo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Response;

class ArchivoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function store(Request $request)
    {
        // Validar archivo según reglas del modelo
        $request->validate(Archivo::$validables);

        if (!$request->hasFile('archivo')) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No se recibió ningún archivo.'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $file = $request->file('archivo');

        // Guardar archivo en /storage/app/adjuntos
        $ruta = $file->store('adjuntos');

        // Crear registro en base de datos
        $archivo = Archivo::create([
            'nombre' => $file->getClientOriginalName(),
            'url'    => $ruta, // puedes usar Storage::url($ruta) si usas 'public'
            'tipo'   => $file->getClientMimeType(),
        ]);

        return response()->json([
            'status' => 'success',
            'data'   => $archivo,
        ]);
    }
    /**
     * Descargar un archivo si es necesario.
     */
    public function download(Archivo $archivo)
    {
        if (!Storage::exists($archivo->url)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Archivo no encontrado.'
            ], 404);
        }

        return Storage::download($archivo->url, $archivo->nombre);
    }
}

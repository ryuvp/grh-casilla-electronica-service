<?php

namespace App\Http\Controllers;

use App\Http\Resources\CasillaResource;
use App\Models\Casilla;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * CasillaController
 *
 * Gestiona casillas electrónicas.
 * Nota: la autorización por permisos/roles se gestiona en frontend.
 * En backend solo se valida token y reglas de negocio de datos.
 */
class CasillaController extends Controller
{
    /**
     * Display a listing of the resource.
     * 
     * Regla B-02: Paginación obligatoria con per_page=10 por defecto, máximo 100.
     * Todos los resultados paginados con ordenamiento consistente.
     */
    public function index(Request $request)
    {
        // B-02: Paginación obligatoria con per_page=10 por defecto, max=100
        $perPage = (int) ($request->per_page ?? 10);
        $perPage = min($perPage, 100);

        $query = Casilla::query()->filter($request);

        // Orden por defecto: id ascendente
        if (!$request->filled('order')) {
            $query->orderBy('id', 'asc');
        }

        // Paginación obligatoria
        $result = $query->paginate($perPage);
        
        return CasillaResource::collection($result);
    }

    /**
     * Endpoint no utilizado en API REST JSON.
     */
    public function create()
    {
        //
    }

    /**
     * Crea una nueva casilla.
     *
     * Flujo:
     * 1. Valida payload de entrada.
     * 2. Persiste en transaccion para mantener consistencia.
     * 3. Retorna recurso serializado.
     */
    public function store(Request $request)
    {
        // Valida estructura y reglas de negocio base de la entidad Casilla.
        $validator = Validator::make($request->all(), Casilla::$validables);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Usa transaccion para mantener atomicidad de la operacion.
            DB::beginTransaction();
            $casilla = Casilla::create($validator->validated());
            DB::commit();
            return (new CasillaResource($casilla));

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Error al crear la casilla',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }

    /**
     * Display the specified resource.
     */
    public function show(Casilla $casilla)
    {
        return new CasillaResource($casilla);
    }

    /**
     * Endpoint no utilizado en API REST JSON.
     */
    public function edit(Casilla $casilla)
    {
        //
    }

    /**
     * Actualiza una casilla existente.
     *
     * Flujo:
     * 1. Ajusta reglas de validacion para ignorar unique del registro actual.
     * 2. Valida payload.
     * 3. Actualiza en transaccion.
     */
    public function update(Request $request, Casilla $casilla)
    {
        // Clona reglas base y adapta la regla unique para update.
        $validables = Casilla::$validables;
        $validables['numero'] = [
            'nullable',
            'string',
            'max:255',
            // Asegúrate de que el número sea único, ignorando el ID actual
            Rule::unique('casillas')->ignore($casilla->id),
        ];

        $validator = Validator::make($request->all(), $validables);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Usa transaccion para mantener consistencia ante errores.
            DB::beginTransaction();
            $casilla->update($validator->validated());
            DB::commit();
            return (new CasillaResource($casilla));

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Error al actualizar la casilla',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
        * Elimina logicamente una casilla.
        *
        * Se aplica soft delete para preservar trazabilidad operativa.
     */
    public function destroy(Casilla $casilla)
    {
        try {
            DB::beginTransaction();

            // Soft delete: conserva trazabilidad y permite restauracion futura.
            $casilla->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Casilla eliminada correctamente.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Error al eliminar la casilla',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

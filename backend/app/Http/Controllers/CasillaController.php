<?php

namespace App\Http\Controllers;

use App\Http\Resources\CasillaResource;
use App\Models\Casilla;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CasillaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = (int) ($request->per_page ?? 50);
        $page    = (int) ($request->page ?? 1);
        $perPage = min($perPage, 100); // 🔒 Máximo

        $query = Casilla::query()->filter($request);

        // Orden por defecto si no viene en request
        if (!$request->filled('order')) {
            $query->orderBy('id', 'asc');
        }
        // Si NO hay paginación, aplicar limit solo si se envía en el request
        if ($request->filled('limit')) {
            $query->limit((int) $request->limit);
        }

        // Si hay paginación
        if ($request->filled('per_page')) {
            $result = $query->paginate($perPage, ['*'], 'page', $page);
            return CasillaResource::collection($result);
        }
        $result = $query->get();
        // Si piden hash
        if ($request->has('hash')) {
            return md5(json_encode(
                CasillaResource::collection($result),
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ));
        }
        return CasillaResource::collection($result);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), Casilla::$validables);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], Response::HTTP_BAD_REQUEST);
        }

        try{
            DB::beginTransaction();
            $casilla = Casilla::create($validator->validated());
            DB::commit();
            return (new CasillaResource($casilla));

        }catch(\Exception $e){
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
     * Show the form for editing the specified resource.
     */
    public function edit(Casilla $casilla)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Casilla $casilla)
    {   
        $validables = Casilla::$validables;
        // Aquí puedes agregar reglas específicas para la actualización si es necesario
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

        try{
            DB::beginTransaction();
            $casilla->update($validator->validated());
            DB::commit();
            return (new CasillaResource($casilla));

        }catch(\Exception $e){
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Error al actualizar la casilla',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Casilla $casilla)
    {
        return response()->json([
            'status' => 'error',
            'message' => 'No está permitido eliminar una casilla'
        ], Response::HTTP_METHOD_NOT_ALLOWED);
    }
}

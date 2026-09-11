<?php

namespace App\Http\Controllers;

use App\Models\Logs;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * LogsController.
 *
 * Expone operaciones de consulta y mantenimiento de bitacora tecnica.
 */
class LogsController extends Controller
{
    /**
     * Lista logs aplicando filtros dinamicos del trait Filterable.
     */
    public function index(Request $request)
    {
        // Paginación obligatoria: per_page=10 por defecto, máx. 100.
        $perPage = min((int) ($request->per_page ?? 10), 100);
        $page = max((int) ($request->page ?? 1), 1);

        $query = Logs::filter($request);
        if (! $request->filled('order')) {
            $query->orderBy('id', 'desc');
        }

        $result = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $result->items(),
            'meta' => [
                'current_page' => $result->currentPage(),
                'per_page'     => $result->perPage(),
                'total'        => $result->total(),
                'last_page'    => $result->lastPage(),
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Endpoint no implementado para creacion manual de logs.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Endpoint no implementado para detalle individual.
     */
    public function show(Logs $logs)
    {
        //
    }

    /**
     * Endpoint no implementado para actualizacion de logs.
     */
    public function update(Request $request, Logs $logs)
    {
        //
    }

    /**
     * Endpoint no implementado para eliminacion de logs.
     */
    public function destroy(Logs $logs)
    {
        //
    }
}

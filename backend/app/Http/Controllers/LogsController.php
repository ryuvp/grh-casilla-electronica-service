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
        // Retorna coleccion filtrada de logs para auditoria tecnica.
        return response()->json([
            'data' => Logs::filter($request)->get(),
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

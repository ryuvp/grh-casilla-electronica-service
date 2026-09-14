<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Filters\Filterable;

/**
 * Modelo Casilla.
 *
 * Representa el buzon electronico de una PERSONA (usuario_id), no de un
 * cargo/designacion: la identidad de la casilla es quien la usa, para que
 * conserve su historial de mensajes sin importar cuantas veces cambie de
 * designacion/cargo dentro de la entidad. `designacion_id` se conserva solo
 * como dato de referencia/legado y no participa en la resolucion de
 * identidad. Incluye soft delete para trazabilidad administrativa.
 */
class Casilla extends Model
{
    use HasFactory, Filterable, SoftDeletes;

    /**
     * Campos asignables masivamente.
     */
    protected $fillable = [
        'numero',
        'tipo',
        'usuario_id',
        'designacion_id',
        'dni',
        'persona_id',
        'nombre_externo',
        'activo',
        'fecha_inicio',
        'fecha_fin',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Reglas base de validacion para create/update.
     *
     * `tipo` es solo descriptivo (interno = personal de la entidad, externo =
     * ciudadano/administrado); la identidad real es `usuario_id` (cuando la
     * persona ya tiene cuenta) y/o `dni` (mientras no la tenga). El DNI
     * peruano son 8 digitos numericos.
     */
    public static $validables = [
        'numero'          => 'required|string|max:255|unique:casillas,numero',
        'tipo'            => 'nullable|in:interno,externo',
        'usuario_id'      => 'nullable|integer|min:1',
        'designacion_id'  => 'nullable|integer|min:1',
        'dni'             => 'nullable|digits:8',
        'persona_id'      => 'nullable|integer|min:1',
        'nombre_externo'  => 'nullable|string|max:255',
        'activo'          => 'nullable|boolean',
        'fecha_inicio'    => 'nullable|date',
        'fecha_fin'       => 'nullable|date|after_or_equal:fecha_inicio',
    ];
    public static $filters = [
        'id',
        'numero',
        'tipo',
        'usuario_id',
        'designacion_id',
        'dni',
        'persona_id',
        'activo',
        'fecha_inicio',
        'fecha_fin',
    ];
}

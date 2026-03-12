<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Filters\Filterable;

/**
 * Modelo Casilla.
 *
 * Representa un buzon electronico asignado a una designacion activa.
 * Incluye soft delete para trazabilidad administrativa.
 */
class Casilla extends Model
{
    use HasFactory, Filterable, SoftDeletes;

    /**
     * Campos asignables masivamente.
     */
    protected $fillable = [
        'numero',
        'designacion_id',
        'activo',
        'fecha_inicio',
        'fecha_fin',
    ];

    /**
     * Reglas base de validacion para create/update.
     */
    public static $validables = [
        'numero'        => 'required|string|max:255|unique:casillas,numero',
        'designacion_id'=> 'required|integer|min:1',
        'activo'        => 'nullable|boolean',
        'fecha_inicio'  => 'nullable|date',
        'fecha_fin'     => 'nullable|date|after_or_equal:fecha_inicio',
    ];
    public static $filters = [
        'numero',
        'designacion_id',
        'activo',
        'fecha_inicio',
        'fecha_fin',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Filters\Filterable;

/**
 * Modelo Casilla.
 *
 * Representa un buzon electronico asignado a un titular (usuario/dependencia).
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
        'titular_tipo',
        'titular_id',
        'activo',
        'fecha_inicio',
        'fecha_fin',
    ];

    /**
     * Reglas base de validacion para create/update.
     */
    public static $validables = [
        'numero'        => 'required|string|max:255|unique:casillas,numero',
        'titular_tipo'  => 'required|integer|in:1,2,3',
        'titular_id'    => 'required|integer',
        'activo'        => 'nullable|boolean',
        'fecha_inicio'  => 'nullable|date',
        'fecha_fin'     => 'nullable|date|after_or_equal:fecha_inicio',
    ];
    public static $filters = [
        'numero',
        'titular_tipo',
        'titular_id',
        'activo',
        'fecha_inicio',
        'fecha_fin',
    ];

    /**
     * Mapa de etiquetas para el tipo de titular.
     */
    const tipoMap = [
        1 => 'Usuario',
        2 => 'Dependencia',
        3 => 'Dependencia Externa',
        4 => 'Otros',
    ];

    /**
     * Accesor para exponer nombre legible del tipo de titular.
     */
    public function getTipoNombreAttribute()
    {
        return self::tipoMap[$this->titular_tipo] ?? '';
    }
}

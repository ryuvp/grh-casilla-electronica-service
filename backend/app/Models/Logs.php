<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Filters\Filterable;

/**
 * Modelo Logs.
 *
 * Centraliza registros tecnicos de operaciones relevantes del servicio.
 */
class Logs extends Model
{
    use Filterable;

    /**
     * Campos asignables masivamente para el registro de log.
     */
    protected $fillable = [
        'table_name', 
        'table_id',
        'usuario_operador_id',
        'title',
        'content',
        'ip_address'
    ];

    /**
     * Campos disponibles para filtros dinamicos.
     */
    protected $filters = [
        'id',
        'table_name', 
        'table_id',
        'usuario_operador_id',
        'title',
        'content',
        'ip_address'
    ];
}

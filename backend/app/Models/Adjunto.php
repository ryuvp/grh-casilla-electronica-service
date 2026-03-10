<?php

namespace App\Models;

use App\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Adjunto.
 *
 * Representa una referencia externa asociada a un mensaje.
 * El tipo de referencia se define en `tipo` y el identificador externo
 * se almacena en `referencia_id`.
 */
class Adjunto extends Model
{
    use Filterable;

    /**
     * Campos asignables masivamente.
     */
    protected $fillable = [
        'mensaje_id',
        'referencia_id',
        'tipo',
    ];

    /**
     * Campos permitidos para filtros dinamicos.
     */
    protected $filters =[
        'mensaje_id',
        'referencia_id',
        'tipo',
    ];

    /**
     * Reglas base de validacion para referencias externas.
     */
    public static $validables = [
        'mensaje_id' => 'required|unsignedBigInteger',
        'referencia_id' => 'required|unsignedBigInteger',
        'tipo' => 'required|string|in:archivo,documento_sgd,normatividad',
    ];
}

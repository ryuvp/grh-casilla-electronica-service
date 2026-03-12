<?php

namespace App\Models;

use App\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo Mensaje para casillas electrónicas.
 *
 * Las referencias externas se almacenan en la tabla `adjuntos`
 * y se diferencian por `tipo`:
 * - archivo
 * - documento_sgd
 * - normatividad
 */
class Mensaje extends Model
{
    use Filterable, SoftDeletes;

    /**
     * Campos asignables masivamente.
     */
    protected $fillable = [
        'asunto',
        'prioridad',
        'contenido',
        'leido',
        'destacado',
        'archivado',
        'casilla_origen_id',
        'casilla_destino_id',
        'read_at',
    ];

    protected $filters = [
        'asunto',
        'prioridad',
        'contenido',
        'destacado',
        'archivado',
        'casilla_origen_id',
        'casilla_destino_id',
        'created_at',
        'read_at',
    ];

    /**
     * Casts de fechas para serializacion consistente.
     */
    protected $casts = [
        'created_at' => 'datetime',
        'read_at' => 'datetime',
        'destacado' => 'boolean',
        'archivado' => 'boolean',
    ];

    /**
     * Reglas de validación para mensajes.
     *
     * sgd_referencias llega por payload y luego se persiste en `adjuntos`.
     * - documento_id: integer, ID del documento en SGD service
     * - tipo: string, tipo de documento (ej: HOJA_DE_RUTA, INFORME, MEMORANDUM)
     */
    public static $validables = [
        'asunto'               => 'required|string|max:255',
        'prioridad'            => 'nullable|integer|between:1,5',
        'contenido'            => 'required|string',
        'sgd_referencias'      => 'nullable|array',
        'sgd_referencias.*.documento_id' => 'required|integer',
        'sgd_referencias.*.tipo'         => 'required|string|max:100',
        'casilla_destino_id'            => 'required|integer|min:1',
        'leido'                => 'nullable|boolean',
    ];

    /**
     * Relacion con adjuntos/referencias externas del mensaje.
     */
    public function adjuntos()
    {
        return $this->hasMany(Adjunto::class, 'mensaje_id');
    }
}

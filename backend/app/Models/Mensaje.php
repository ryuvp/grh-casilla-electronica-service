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
        'usuario_origen_id',
        'usuario_destino_id',
        'fecha_envio',
        'fecha_leido',
    ];

    protected $filters = [
        'asunto',
        'prioridad',
        'contenido',
        'ususario_origen_id',
        'fecha_envio',
        'fecha_leido',
    ];

    /**
     * Casts de fechas para serializacion consistente.
     */
    protected $casts = [
        'fecha_envio' => 'datetime',
        'fecha_leido' => 'datetime',
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
        'usuario_destino_id'   => 'required|integer',
        'fecha_envio'          => 'nullable|date',
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

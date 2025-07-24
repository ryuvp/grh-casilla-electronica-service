<?php

namespace App\Models;

use App\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;

class Mensaje extends Model
{
    use Filterable;
    protected $fillable = [
        'asunto',
        'prioridad',
        'contenido',
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
    public static $validables = [
        'asunto'               => 'required|string|max:255',
        'prioridad'            => 'nullable|integer|between:1,5',
        'contenido'            => 'required|string',
        'usuario_destino_id'   => 'required|integer',
        'fecha_envio'          => 'nullable|date',
        'leido'                => 'nullable|boolean',
    ];
    public function adjuntos()
    {
        return $this->hasMany(Adjunto::class, 'mensaje_id');
    }
}

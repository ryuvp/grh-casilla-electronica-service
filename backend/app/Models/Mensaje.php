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
        'ususario_origen_id',
        'fecha_envio',
        'fecha_leido',
    ];
    protected $filters =[
        'asunto',
        'prioridad',
        'contenido',
        'ususario_origen_id',
        'fecha_envio',
        'fecha_leido',
    ];
    public static $validables = [
        'asunto' => 'required|string|max:255',
        'prioridad' => 'required|smallInteger|max:255',
        'contenido' => 'required|text|max:100',
        'ususario_origen_id' => 'required|unsignedBigInteger|max:100',
        'ususario_destino_id' => 'required|unsignedBigInteger|max:100',
        'fecha_envio' => 'required|date|max:100',
        'fecha_leido' => 'required|date|max:100',
    ]; 
}

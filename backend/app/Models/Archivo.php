<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Archivo extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'url',
        'tipo',
    ];

    public static $validables = [
        'archivo' => 'required|file|max:10240',
    ];

    public function mensajes()
    {
        return $this->belongsToMany(Mensaje::class, 'adjuntos')->withTimestamps()->withPivot('id');
    }
}

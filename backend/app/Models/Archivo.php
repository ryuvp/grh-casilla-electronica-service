<?php

namespace App\Models;

use App\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;

class Archivo extends Model
{
    use Filterable;
    protected $fillable = [
        'mensaje_id',
        'archivo_id',
    ];
    protected $filters =[
        'mensaje_id',
        'archivo_id',
    ];
    public static $validables = [
        'mensaje_id' => 'required|unsignedBigInteger',
        'archivo_id' => 'required|unsignedBigInteger',
    ]; 
}

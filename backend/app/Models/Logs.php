<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Filters\Filterable;

class Logs extends Model
{
    use Filterable;
    protected $fillable = [
        'table_name', 
        'table_id',
        'usuario_operador_id',
        'title',
        'content',
        'ip_address'
    ];
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

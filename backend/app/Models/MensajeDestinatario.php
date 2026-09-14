<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pivote de destinatarios por mensaje.
 *
 * Registra el estado de lectura de forma independiente por cada casilla
 * notificada, para que un envio masivo (multiples destinatarios internos
 * y/o externos) genere una constancia de lectura propia por destinatario.
 */
class MensajeDestinatario extends Model
{
    protected $table = 'mensaje_destinatarios';

    protected $fillable = [
        'mensaje_id',
        'casilla_id',
        'leido',
        'read_at',
    ];

    protected $casts = [
        'leido' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function mensaje()
    {
        return $this->belongsTo(Mensaje::class, 'mensaje_id');
    }

    public function casilla()
    {
        return $this->belongsTo(Casilla::class, 'casilla_id');
    }
}

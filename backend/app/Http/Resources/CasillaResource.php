<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CasillaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'numero'         => $this->numero,
            'tipo'           => $this->tipo,
            'usuario_id'     => $this->usuario_id,
            'designacion_id' => $this->designacion_id,
            'dni'            => $this->dni,
            'persona_id'     => $this->persona_id,
            'nombre_externo' => $this->nombre_externo,
            'activo'         => $this->activo,
            'fecha_inicio'   => $this->fecha_inicio,
            'fecha_fin'      => $this->fecha_fin,
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,
        ];
    }
}

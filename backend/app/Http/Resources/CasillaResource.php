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
            'id'            => $this->id,
            'numero'        => $this->numero,
            'titular_tipo'  => $this->titular_tipo,
            'titular_id'    => $this->titular_id,
            'tipo_nombre'   => $this->tipo_nombre,
            'activo'        => $this->activo,
            'fecha_inicio'  => $this->fecha_inicio,
            'fecha_fin'     => $this->fecha_fin,
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
        ];
    }
}

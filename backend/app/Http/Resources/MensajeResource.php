<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MensajeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'asunto'              => $this->asunto,
            'contenido'           => $this->contenido,
            'html_contenido'      => $this->html_contenido ?? null,
            'prioridad'           => $this->prioridad,
            'leido'               => $this->leido,
            'fecha_envio'         => $this->fecha_envio,
            'fecha_leido'         => $this->fecha_leido,
            'usuario_origen_id'   => $this->usuario_origen_id,
            'usuario_destino_id'  => $this->usuario_destino_id,
            'archivos'            => ArchivoResource::collection($this->whenLoaded('archivos')),
        ];
    }
}

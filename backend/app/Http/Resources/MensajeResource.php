<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * MensajeResource
 * 
 * Transforma el modelo Mensaje a JSON para respuestas API.
 * 
 * Incluye dos tipos de adjuntos:
 * - archivo_ids: IDs de archivos en File Service (via relación adjuntos)
 * - sgd_referencias: Referencias a documentos SGD (via campo JSON sgd_referencias)
 */
class MensajeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $adjuntos = $this->adjuntos ?? collect();

        // Archivos tradicionales del file-service.
        $archivoIds = $adjuntos
            ->where('tipo', 'archivo')
            ->pluck('referencia_id')
            ->filter()
            ->values();

        // Referencias de documentos SGD.
        $sgdReferencias = $adjuntos
            ->where('tipo', 'documento_sgd')
            ->map(fn ($adjunto) => [
                'documento_id' => $adjunto->referencia_id,
                'tipo' => 'documento_sgd',
            ])
            ->values();

        // Referencias de normatividad.
        $normatividadReferencias = $adjuntos
            ->where('tipo', 'normatividad')
            ->map(fn ($adjunto) => [
                'normatividad_id' => $adjunto->referencia_id,
                'tipo' => 'normatividad',
            ])
            ->values();

        return [
            'id'                  => $this->id,
            'asunto'              => $this->asunto,
            'contenido'           => $this->contenido,
            'prioridad'           => $this->prioridad,
            'leido'               => $this->leido,
            'fecha_envio'         => $this->fecha_envio,
            'fecha_leido'         => $this->fecha_leido,
            'usuario_origen_id'   => $this->usuario_origen_id,
            'usuario_destino_id'  => $this->usuario_destino_id,

            // Referencias externas tipadas (fuente unica: tabla adjuntos).
            'archivo_ids'         => $archivoIds,
            'sgd_referencias'     => $sgdReferencias,
            'normatividad_referencias' => $normatividadReferencias,
        ];
    }
}

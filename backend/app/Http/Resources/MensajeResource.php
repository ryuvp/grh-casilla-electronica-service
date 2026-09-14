<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * MensajeResource
 * 
 * Transforma el modelo Mensaje a JSON para respuestas API.
 * 
 * Incluye referencias tipadas asociadas al mensaje:
 * - archivo_ids: IDs de archivos en File Service (via relación adjuntos)
 * - sgd_referencias: Referencias a documentos SGD
 * - normatividad_referencias: Referencias normativas
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

        // Destinatarios completos del envio (incluye al primario), con su
        // estado de lectura individual. Fuente unica: tabla mensaje_destinatarios.
        $destinatarios = $this->relationLoaded('destinatarios')
            ? $this->destinatarios->map(fn ($d) => [
                'casilla_id' => $d->casilla_id,
                'leido'      => $d->leido,
                'read_at'    => $d->read_at,
            ])->values()
            : null;

        return [
            'id'                  => $this->id,
            'asunto'              => $this->asunto,
            'contenido'           => $this->contenido,
            'prioridad'           => $this->prioridad,
            'leido'               => $this->leido,
            'destacado'           => $this->destacado,
            'archivado'           => $this->archivado,
            'created_at'          => $this->created_at,
            'read_at'             => $this->read_at,
            'casilla_origen_id'   => $this->casilla_origen_id,
            'casilla_destino_id'  => $this->casilla_destino_id,
            'casilla_destino_ids' => $destinatarios?->pluck('casilla_id')->values(),
            'destinatarios'       => $destinatarios,

            // Referencias externas tipadas (fuente unica: tabla adjuntos).
            'archivo_ids'         => $archivoIds,
            'sgd_referencias'     => $sgdReferencias,
            'normatividad_referencias' => $normatividadReferencias,
        ];
    }
}

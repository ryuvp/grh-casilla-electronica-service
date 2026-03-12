<?php

namespace Database\Seeders;

use App\Models\Casilla;
use App\Models\Mensaje;
use Illuminate\Database\Seeder;

class CasillaMensajesDemoSeeder extends Seeder
{
    /**
     * Seed de demo para validar mensajeria entre casillas.
     */
    public function run(): void
    {
        $casillaAdmin = Casilla::updateOrCreate(
            ['designacion_id' => 25267],
            [
                'numero' => 'CAS-25267-ADMIN',
                'activo' => true,
                'fecha_inicio' => '2026-03-10',
                'fecha_fin' => null,
            ]
        );

        $casillaKevin = Casilla::updateOrCreate(
            ['designacion_id' => 5161],
            [
                'numero' => 'CAS-5161-KEVIN',
                'activo' => true,
                'fecha_inicio' => '2026-03-10',
                'fecha_fin' => null,
            ]
        );

        Mensaje::updateOrCreate(
            ['asunto' => 'Demo: Admin a Kevin'],
            [
                'contenido' => 'Mensaje de prueba desde Admin hacia Kevin (persona natural).',
                'prioridad' => 2,
                'leido' => false,
                'destacado' => true,
                'archivado' => false,
                'casilla_origen_id' => $casillaAdmin->id,
                'casilla_destino_id' => $casillaKevin->id,
                'read_at' => null,
            ]
        );

        Mensaje::updateOrCreate(
            ['asunto' => 'Demo: Recordatorio administrativo'],
            [
                'contenido' => 'Notificacion adicional emitida por Admin para Kevin.',
                'prioridad' => 3,
                'leido' => false,
                'destacado' => false,
                'archivado' => false,
                'casilla_origen_id' => $casillaAdmin->id,
                'casilla_destino_id' => $casillaKevin->id,
                'read_at' => null,
            ]
        );
    }
}

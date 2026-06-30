<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_mensajes_dest_arch_created
            ON mensajes (casilla_destino_id, archivado, created_at DESC)');

        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_mensajes_dest_destacado_arch
            ON mensajes (casilla_destino_id, destacado, archivado, created_at DESC)');

        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_mensajes_origen_created
            ON mensajes (casilla_origen_id, created_at DESC)');

        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_casillas_desig_id_desc
            ON casillas (designacion_id, id DESC)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_mensajes_dest_arch_created');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_mensajes_dest_destacado_arch');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_mensajes_origen_created');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_casillas_desig_id_desc');
    }
};

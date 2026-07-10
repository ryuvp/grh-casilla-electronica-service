<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        // Activar la extensión pg_trgm para soportar búsquedas de texto parcial e insensible a mayúsculas con ILIKE
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        // Índice trigram GIN para búsquedas rápidas en el Asunto
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_mensajes_asunto_trgm 
            ON mensajes USING gin (asunto gin_trgm_ops)');

        // Índice trigram GIN para búsquedas rápidas en el Contenido
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_mensajes_contenido_trgm 
            ON mensajes USING gin (contenido gin_trgm_ops)');

        // Índice parcial para optimizar al 100% el conteo y consulta de no leídos en la bandeja de entrada
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_mensajes_unread_count 
            ON mensajes (casilla_destino_id) WHERE leido = false AND deleted_at IS NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_mensajes_asunto_trgm');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_mensajes_contenido_trgm');
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS idx_mensajes_unread_count');
    }
};

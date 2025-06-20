<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->string('table_name')->comment('Nombre de la tabla afectada por el log');
            $table->unsignedBigInteger('table_id')->index()->comment('ID del registro afectado en la tabla');
            $table->unsignedBigInteger('usuario_operador_id')->nullable()->index()->comment('ID del usuario operador asociado al log');
            $table->string('title');
            $table->json('content');
            $table->ipAddress('ip_address')->nullable()->comment('Dirección IP del usuario que generó el log');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logs');
    }
};

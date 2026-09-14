<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de destinatarios por mensaje: permite despacho a multiples casillas
     * (internas y/o externas) manteniendo `mensajes.casilla_destino_id` como
     * espejo del destinatario primario para no romper flujos existentes de
     * un solo destinatario (bandejas, certificados PDF, etc).
     *
     * El estado de lectura se registra por destinatario, ya que cada casilla
     * notificada debe tener su propia constancia de lectura independiente.
     */
    public function up(): void
    {
        Schema::create('mensaje_destinatarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mensaje_id')->constrained('mensajes')->cascadeOnDelete();
            $table->unsignedBigInteger('casilla_id')->index();
            $table->boolean('leido')->default(false);
            $table->dateTime('read_at')->nullable();
            $table->timestamps();

            $table->unique(['mensaje_id', 'casilla_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mensaje_destinatarios');
    }
};

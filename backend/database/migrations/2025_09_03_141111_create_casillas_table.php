<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Casilla electronica: representa el buzon de una PERSONA, no de un
     * cargo/designacion.
     *
     * - `usuario_id` es el ancla real de identidad (id estable del Usuario en
     *   Auth Service): permite que una persona conserve su casilla y su
     *   historial de mensajes sin importar cuantas veces cambie de
     *   designacion/cargo dentro de la entidad.
     * - `dni` identifica a una persona externa (ciudadano/administrado) antes
     *   de que tenga cuenta/usuario_id; una vez lo tiene, ambos quedan
     *   enlazados en la misma fila.
     * - `designacion_id` se conserva solo como dato de referencia (la
     *   designacion mas reciente por la que se ubico a esta persona), para
     *   compatibilidad con herramientas de busqueda que aun consultan una
     *   casilla por designacion; nunca participa en la resolucion de
     *   identidad.
     * - `tipo` (interno/externo) es puramente informativo, para distinguir
     *   personal de la entidad de ciudadanos/administrados en pantallas
     *   administrativas.
     */
    public function up(): void
    {
        Schema::create('casillas', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->string('tipo', 20)->default('interno');
            $table->unsignedBigInteger('designacion_id')->nullable()->index();
            $table->string('dni', 8)->nullable()->unique();
            $table->unsignedBigInteger('usuario_id')->nullable()->unique();
            $table->unsignedBigInteger('persona_id')->nullable();
            $table->string('nombre_externo')->nullable();
            $table->boolean('activo')->default(true);
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tipo', 'dni']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('casillas');
    }
};

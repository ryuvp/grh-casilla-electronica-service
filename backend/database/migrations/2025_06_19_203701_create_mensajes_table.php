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
        Schema::create('mensajes', function (Blueprint $table) {
            $table->id();
            $table->string('asunto');
            $table->smallInteger('prioridad');
            $table->text('contenido');
            $table->boolean('leido')->default(false);
            $table->unsignedBigInteger('casilla_origen_id')->index();
            $table->unsignedBigInteger('casilla_destino_id')->index();
            $table->dateTime('read_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mensajes');
    }
};

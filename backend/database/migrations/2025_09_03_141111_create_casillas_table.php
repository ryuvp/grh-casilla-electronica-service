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
        Schema::create('casillas', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->smallInteger('titular_tipo')->comment('1: usuario, 2: dependencia, 3: dependencia_externa ...');
            $table->unsignedBigInteger('titular_id');
            $table->boolean('activo')->default(true);
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->timestamps();
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

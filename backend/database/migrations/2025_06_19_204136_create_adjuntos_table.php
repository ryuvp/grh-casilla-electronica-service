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
        Schema::create('adjuntos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mensaje_id');
            $table->unsignedBigInteger('referencia_id');
            $table->string('tipo', 30)->default('archivo');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['mensaje_id', 'tipo']);
            $table->index(['tipo', 'referencia_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adjuntos');
    }
};

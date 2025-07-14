<?php

use App\Models\Administracion\Usuario;
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
            $table->unsignedBigInteger('usuario_origen_id');
            $table->unsignedBigInteger('usuario_destino_id');
            $table->date('fecha_envio')->nullable();
            $table->date('fecha_leido')->nullable();
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

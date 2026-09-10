<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Eventos publicados en el portal.
 *
 * EventsController recibia un {id} por la ruta y lo ignoraba por completo,
 * devolviendo siempre el mismo evento con texto Lorem Ipsum. Aqui cada
 * evento es una fila real y la ruta resuelve por slug.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eventos', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->string('nombre', 150);
            $table->string('resumen', 255);
            $table->text('descripcion');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->string('horario', 80);
            $table->string('imagen', 180);
            $table->foreignId('escenario_id')->nullable()->constrained('escenarios')->nullOnDelete();
            $table->boolean('destacado')->default(false);
            $table->timestamps();

            $table->index(['fecha_inicio', 'fecha_fin']);
            $table->index('destacado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eventos');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Escenarios disponibles para reserva.
 *
 * En la version de 2019 estos datos vivian dentro de ScenariosController
 * como arreglos PHP escritos a mano, con el mismo escenario devuelto sin
 * importar que tipo se pidiera por la ruta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('escenarios', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 60)->unique();
            $table->string('nombre', 120);
            $table->string('resumen', 255);
            $table->text('descripcion')->nullable();
            $table->decimal('precio_dia', 12, 2);
            $table->unsignedInteger('capacidad');
            $table->string('imagen_principal', 180);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('escenarios');
    }
};

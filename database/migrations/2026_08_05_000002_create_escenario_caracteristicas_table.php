<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Caracteristicas de cada escenario.
 *
 * El codigo original guardaba estas descripciones como cuatro campos fijos
 * (desc1, desc2, desc3, desc4) dentro del mismo arreglo. Normalizarlas
 * permite que un escenario tenga tantas caracteristicas como necesite.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('escenario_caracteristicas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('escenario_id')->constrained('escenarios')->cascadeOnDelete();
            $table->string('titulo', 120);
            $table->string('descripcion', 400);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();

            $table->index(['escenario_id', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('escenario_caracteristicas');
    }
};

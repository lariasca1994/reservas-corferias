<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Galeria de imagenes por escenario.
 *
 * Reemplaza los campos img1..img4 del arreglo original y el arreglo de
 * rutas que estaba duplicado dentro de public/js/reservation-form.js.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('escenario_imagenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('escenario_id')->constrained('escenarios')->cascadeOnDelete();
            $table->string('ruta', 180);
            $table->string('texto_alternativo', 160);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();

            $table->index(['escenario_id', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('escenario_imagenes');
    }
};

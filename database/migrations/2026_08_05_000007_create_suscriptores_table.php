<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lista de distribucion para el aviso de eventos nuevos.
 *
 * Se guarda un token de baja para que cada correo lleve su enlace de
 * cancelacion sin exponer el identificador ni requerir sesion. Es un
 * requisito practico de cualquier envio masivo: sin salida facil, los
 * destinatarios marcan el mensaje como spam y se degrada la reputacion
 * del dominio remitente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suscriptores', function (Blueprint $table) {
            $table->id();
            $table->string('email', 150)->unique();
            $table->string('nombre', 120)->nullable();
            $table->string('token_baja', 64)->unique();
            $table->timestamp('confirmado_en')->nullable();
            $table->timestamp('baja_en')->nullable();
            $table->timestamps();

            $table->index('baja_en');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suscriptores');
    }
};

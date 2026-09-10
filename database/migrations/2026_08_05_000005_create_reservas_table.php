<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reservas de escenarios.
 *
 * En 2019 las fechas ocupadas eran cuatro arreglos literales dentro de
 * ReservationController y nada se persistia: el formulario respondia
 * "reserved" y la reserva se perdia al terminar la peticion.
 *
 * El indice compuesto sobre (escenario_id, fecha_inicio, fecha_fin) es el
 * que sostiene la consulta de solapamiento, que es la operacion mas
 * frecuente y la mas critica del dominio.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservas', function (Blueprint $table) {
            $table->id();

            // Referencia publica de la reserva. Es la que se comparte con el
            // cliente, la que viaja en el correo y la que codifica el QR.
            // Se usa en lugar del id autoincremental para no exponer el
            // volumen de reservas del sistema.
            $table->string('codigo', 20)->unique();

            $table->foreignId('escenario_id')->constrained('escenarios')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('nombre_contacto', 120);
            $table->string('email_contacto', 150);
            $table->string('telefono_contacto', 40);

            $table->date('fecha_inicio');
            $table->date('fecha_fin');

            // pendiente | confirmada | cancelada
            $table->string('estado', 20)->default('pendiente');
            $table->string('observaciones', 500)->nullable();

            $table->timestamps();

            $table->index(['escenario_id', 'fecha_inicio', 'fecha_fin'], 'idx_reservas_ocupacion');
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservas');
    }
};

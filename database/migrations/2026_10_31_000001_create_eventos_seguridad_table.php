<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Eventos de seguridad de las cuentas (ver App\Services\Auditoria): solo el tipo, la cuenta
 * y la fecha. Se depuran a los 15 dias.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eventos_seguridad', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 40);
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('fecha')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eventos_seguridad');
    }
};

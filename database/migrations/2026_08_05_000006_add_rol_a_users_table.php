<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Roles de acceso al panel administrativo.
 *
 * En 2019 existia una vista de login y otra de registro, pero ninguna
 * autenticaba: el formulario no tenia action ni method, y la unica
 * validacion era una expresion regular en jQuery sobre el formato del
 * correo. No habia usuarios, sesiones ni permisos de ningun tipo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // administrador | operador | cliente
            $table->string('rol', 20)->default('cliente');
            $table->boolean('activo')->default(true);
            $table->string('telefono', 40)->nullable();

            $table->index('rol');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['rol']);
            $table->dropColumn(['rol', 'activo', 'telefono']);
        });
    }
};

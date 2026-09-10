<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

/**
 * Crea o promueve una cuenta adicional de administrador, sin tocar la
 * cuenta "oficial" que siembra UsuarioSeeder (esa sigue leyendo
 * ADMIN_EMAIL/ADMIN_PASSWORD del .env). Esta es la cuenta de revision del
 * portafolio, igual en espiritu a los comandos crear_admin de Gestor de
 * Casos QA y TaskFlow, y a /admin/pagos en PRPagos.
 *
 *   php artisan usuarios:crear-admin correo@ejemplo.com "Nombre" contrasena
 *
 * Si el correo ya existe, se promueve a administrador y se activa; el
 * valor de <contrasena> se ignora en ese caso (puede ser cualquier cosa).
 */
class CrearAdmin extends Command
{
    protected $signature = 'usuarios:crear-admin {email} {nombre} {password}';

    protected $description = 'Crea o promueve una cuenta con rol administrador';

    public function handle(): int
    {
        $email = strtolower($this->argument('email'));
        $nombre = $this->argument('nombre');
        $password = $this->argument('password');

        $existente = User::where('email', $email)->first();

        if ($existente) {
            $existente->forceFill([
                'rol' => User::ROL_ADMINISTRADOR,
                'activo' => true,
            ])->save();

            $this->info("La cuenta {$email} ya existía: se promovió a administrador.");

            return self::SUCCESS;
        }

        if (strlen($password) < 10) {
            $this->error('La contraseña debe tener al menos 10 caracteres.');

            return self::FAILURE;
        }

        User::create([
            'name' => $nombre,
            'email' => $email,
            'password' => Hash::make($password),
            'rol' => User::ROL_ADMINISTRADOR,
            'activo' => true,
            'email_verified_at' => now(),
        ]);

        $this->info("Cuenta administradora creada para {$email}.");

        return self::SUCCESS;
    }
}

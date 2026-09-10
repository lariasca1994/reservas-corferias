<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Cuentas iniciales del panel.
 *
 * Las contrasenas se leen del entorno y nunca se escriben en el
 * repositorio. Si faltan, el seeder se detiene: crear una cuenta con
 * contrasena vacia produce un usuario que existe pero con el que es
 * imposible iniciar sesion, porque el formulario exige el campo.
 */
class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        $cuentas = [
            [
                'variable' => 'ADMIN_PASSWORD',
                'email'    => env('ADMIN_EMAIL', 'admin@ejemplo.com'),
                'nombre'   => 'Administrador',
                'rol'      => User::ROL_ADMINISTRADOR,
            ],
            [
                'variable' => 'OPERADOR_PASSWORD',
                'email'    => env('OPERADOR_EMAIL', 'operador@ejemplo.com'),
                'nombre'   => 'Operador comercial',
                'rol'      => User::ROL_OPERADOR,
            ],
        ];

        foreach ($cuentas as $cuenta) {
            $clave = env($cuenta['variable']);

            if (blank($clave)) {
                throw new RuntimeException(
                    "Falta {$cuenta['variable']} en el archivo .env. ".
                    'Asigna una contrasena y vuelve a ejecutar el seeder.'
                );
            }

            if (strlen($clave) < 10) {
                throw new RuntimeException(
                    "{$cuenta['variable']} debe tener al menos 10 caracteres."
                );
            }

            User::updateOrCreate(
                ['email' => $cuenta['email']],
                [
                    'name'              => $cuenta['nombre'],
                    'password'          => Hash::make($clave),
                    'rol'               => $cuenta['rol'],
                    'activo'            => true,
                    'email_verified_at' => now(),
                ]
            );

            $this->command?->info("Cuenta lista: {$cuenta['email']} ({$cuenta['rol']})");
        }
    }
}

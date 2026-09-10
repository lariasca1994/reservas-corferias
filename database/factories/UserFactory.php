<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name'              => $this->faker->name(),
            'email'             => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password'          => Hash::make('clave-de-prueba'),
            'remember_token'    => Str::random(10),
            'rol'               => User::ROL_CLIENTE,
            'activo'            => true,
            'telefono'          => $this->faker->numerify('3#########'),
        ];
    }

    public function administrador(): static
    {
        return $this->state(fn () => ['rol' => User::ROL_ADMINISTRADOR]);
    }

    public function operador(): static
    {
        return $this->state(fn () => ['rol' => User::ROL_OPERADOR]);
    }

    public function inactivo(): static
    {
        return $this->state(fn () => ['activo' => false]);
    }

    /** Cuenta creada pero con el correo sin confirmar. */
    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }
}

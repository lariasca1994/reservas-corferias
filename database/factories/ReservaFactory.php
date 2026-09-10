<?php

namespace Database\Factories;

use App\Models\Escenario;
use App\Models\Reserva;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reserva>
 */
class ReservaFactory extends Factory
{
    protected $model = Reserva::class;

    public function definition(): array
    {
        $inicio = now()->addDays($this->faker->numberBetween(1, 90));

        return [
            'escenario_id'      => Escenario::factory(),
            'user_id'           => null,
            'nombre_contacto'   => $this->faker->name(),
            'email_contacto'    => $this->faker->safeEmail(),
            'telefono_contacto' => $this->faker->numerify('3#########'),
            'fecha_inicio'      => $inicio->toDateString(),
            'fecha_fin'         => $inicio->copy()->addDays($this->faker->numberBetween(0, 5))->toDateString(),
            'estado'            => Reserva::ESTADO_CONFIRMADA,
            'observaciones'     => null,
        ];
    }

    /** Reserva con un rango exacto, util para las pruebas de solapamiento. */
    public function entre(string $inicio, string $fin): static
    {
        return $this->state(fn () => [
            'fecha_inicio' => $inicio,
            'fecha_fin'    => $fin,
        ]);
    }

    public function cancelada(): static
    {
        return $this->state(fn () => ['estado' => Reserva::ESTADO_CANCELADA]);
    }

    public function pendiente(): static
    {
        return $this->state(fn () => ['estado' => Reserva::ESTADO_PENDIENTE]);
    }
}

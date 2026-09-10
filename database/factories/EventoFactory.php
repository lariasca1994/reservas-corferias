<?php

namespace Database\Factories;

use App\Models\Evento;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Evento>
 */
class EventoFactory extends Factory
{
    protected $model = Evento::class;

    public function definition(): array
    {
        $nombre = 'Evento '.$this->faker->unique()->words(2, true);
        $inicio = now()->addDays($this->faker->numberBetween(5, 120));

        return [
            'slug'         => Str::slug($nombre).'-'.$this->faker->unique()->numberBetween(1, 99999),
            'nombre'       => $nombre,
            'resumen'      => $this->faker->sentence(12),
            'descripcion'  => $this->faker->paragraphs(3, true),
            'fecha_inicio' => $inicio->toDateString(),
            'fecha_fin'    => $inicio->copy()->addDays($this->faker->numberBetween(0, 6))->toDateString(),
            'horario'      => '9:00 a. m. a 6:00 p. m.',
            'imagen'       => 'images/event1.jpg',
            'escenario_id' => null,
            'destacado'    => false,
        ];
    }

    public function destacado(): static
    {
        return $this->state(fn () => ['destacado' => true]);
    }

    public function finalizado(): static
    {
        return $this->state(fn () => [
            'fecha_inicio' => now()->subDays(20)->toDateString(),
            'fecha_fin'    => now()->subDays(18)->toDateString(),
        ]);
    }
}

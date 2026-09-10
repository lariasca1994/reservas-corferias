<?php

namespace Database\Factories;

use App\Models\Escenario;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Escenario>
 */
class EscenarioFactory extends Factory
{
    protected $model = Escenario::class;

    public function definition(): array
    {
        $nombre = 'Escenario '.$this->faker->unique()->word();

        return [
            'slug'             => Str::slug($nombre).'-'.$this->faker->unique()->numberBetween(1, 99999),
            'nombre'           => $nombre,
            'resumen'          => $this->faker->sentence(10),
            'descripcion'      => $this->faker->paragraph(4),
            'precio_dia'       => $this->faker->numberBetween(5, 40) * 1000000,
            'capacidad'        => $this->faker->numberBetween(200, 5000),
            'imagen_principal' => 'images/slide1.jpg',
            'activo'           => true,
        ];
    }

    public function inactivo(): static
    {
        return $this->state(fn () => ['activo' => false]);
    }
}

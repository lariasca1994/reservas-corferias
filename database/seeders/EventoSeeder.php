<?php

namespace Database\Seeders;

use App\Models\Escenario;
use App\Models\Evento;
use Illuminate\Database\Seeder;

/**
 * Eventos rescatados de IndexController y EventsController.
 *
 * Los dos primeros aparecian en la pagina de inicio como arreglo literal.
 * El tercero corresponde a la imagen Filbo-2018.png que estaba en public
 * pero cuyo evento devolvia texto Lorem Ipsum.
 *
 * Las fechas se calculan relativas a hoy para que la demostracion siempre
 * muestre eventos vigentes, en lugar de quedar congelada en 2018 como el
 * original.
 */
class EventoSeeder extends Seeder
{
    public function run(): void
    {
        $alfa  = Escenario::where('slug', 'alfa')->first();
        $omega = Escenario::where('slug', 'omega')->first();
        $metro = Escenario::where('slug', 'metro')->first();

        $eventos = [
            [
                'slug'         => 'festival-de-comics',
                'nombre'       => 'Festival de Comics',
                'resumen'      => 'Tres dias de comic, disfraces, gastronomia y charlas con autores invitados.',
                'descripcion'  => "El Festival de Comics reune a ilustradores, editoriales independientes y comunidades de aficionados en un mismo espacio.\n\nLa programacion incluye concursos de disfraces, mesas de dibujo en vivo, proyecciones y una zona gastronomica con cocinas de distintas regiones del pais. El acceso a las charlas no tiene costo adicional sobre la entrada general.",
                'fecha_inicio' => now()->addDays(21)->toDateString(),
                'fecha_fin'    => now()->addDays(23)->toDateString(),
                'horario'      => '10:00 a. m. a 8:00 p. m.',
                'imagen'       => 'images/event1.jpg',
                'escenario_id' => $omega?->id,
                'destacado'    => true,
            ],
            [
                'slug'         => 'encuentro-gastronomico',
                'nombre'       => 'Encuentro Gastronomico',
                'resumen'      => 'Talleres practicos para aprender a preparar platos de la cocina colombiana.',
                'descripcion'  => "Un evento pensado para quienes quieren cocinar, no solo mirar.\n\nCada jornada abre con una demostracion a cargo de un chef invitado y continua con estaciones de trabajo donde los asistentes preparan el plato del dia. Los cupos por taller son limitados y se asignan por orden de llegada.",
                'fecha_inicio' => now()->addDays(40)->toDateString(),
                'fecha_fin'    => now()->addDays(42)->toDateString(),
                'horario'      => '9:00 a. m. a 6:00 p. m.',
                'imagen'       => 'images/event2.jpg',
                'escenario_id' => $metro?->id,
                'destacado'    => true,
            ],
            [
                'slug'         => 'feria-del-libro',
                'nombre'       => 'Feria del Libro',
                'resumen'      => 'Editoriales, presentaciones de autor y actividades para colegios.',
                'descripcion'  => "La Feria del Libro ocupa el pabellon completo durante dos semanas.\n\nAdemas de los pabellones editoriales, la programacion contempla presentaciones de novedades, encuentros con autores y una franja dedicada a instituciones educativas en horario de la manana.",
                'fecha_inicio' => now()->addDays(75)->toDateString(),
                'fecha_fin'    => now()->addDays(89)->toDateString(),
                'horario'      => '7:00 a. m. a 8:00 p. m.',
                'imagen'       => 'images/Filbo-2018.png',
                'escenario_id' => $alfa?->id,
                'destacado'    => false,
            ],
        ];

        foreach ($eventos as $datos) {
            Evento::updateOrCreate(['slug' => $datos['slug']], $datos);
        }
    }
}

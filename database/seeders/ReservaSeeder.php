<?php

namespace Database\Seeders;

use App\Models\Escenario;
use App\Models\Reserva;
use Illuminate\Database\Seeder;

/**
 * Reservas de ejemplo para poblar el calendario de ocupacion.
 *
 * Sustituyen los arreglos $starDatesReserved / $endDatesReserved que
 * estaban escritos dentro de ReservationController con fechas fijas de
 * 2019, y que por tanto quedaban en el pasado para siempre.
 */
class ReservaSeeder extends Seeder
{
    public function run(): void
    {
        $bloques = [
            ['alfa',  10, 14, Reserva::ESTADO_CONFIRMADA, 'Feria empresarial regional'],
            ['alfa',  35, 39, Reserva::ESTADO_CONFIRMADA, 'Convencion anual de distribuidores'],
            ['beta',  18, 20, Reserva::ESTADO_PENDIENTE,  'Congreso de ingenieria'],
            ['omega', 21, 23, Reserva::ESTADO_CONFIRMADA, 'Festival de Comics'],
            ['omega', 55, 60, Reserva::ESTADO_CONFIRMADA, 'Gira nacional de conciertos'],
            ['metro', 40, 42, Reserva::ESTADO_CONFIRMADA, 'Encuentro Gastronomico'],
            ['metro',  5,  5, Reserva::ESTADO_CANCELADA,  'Lanzamiento cancelado por el cliente'],
        ];

        foreach ($bloques as [$slug, $desde, $hasta, $estado, $observacion]) {
            $escenario = Escenario::where('slug', $slug)->first();

            if (! $escenario) {
                continue;
            }

            Reserva::updateOrCreate(
                [
                    'escenario_id' => $escenario->id,
                    'fecha_inicio' => now()->addDays($desde)->toDateString(),
                ],
                [
                    'fecha_fin'         => now()->addDays($hasta)->toDateString(),
                    'nombre_contacto'   => 'Area comercial',
                    'email_contacto'    => 'comercial@ejemplo.com',
                    'telefono_contacto' => '3000000000',
                    'estado'            => $estado,
                    'observaciones'     => $observacion,
                ]
            );
        }
    }
}

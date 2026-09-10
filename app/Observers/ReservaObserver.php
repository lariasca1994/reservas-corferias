<?php

namespace App\Observers;

use App\Mail\ReservaCancelada;
use App\Mail\ReservaConfirmada;
use App\Mail\ReservaRegistrada;
use App\Models\Reserva;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Dispara la notificacion correspondiente a cada transicion de estado.
 *
 * Vive en un observador y no en el controlador para que el correo salga
 * sin importar por donde se creo o modifico la reserva: formulario
 * publico, panel administrativo, comando de consola o seeder de
 * demostracion. Una sola regla, un solo lugar.
 */
class ReservaObserver
{
    public function created(Reserva $reserva): void
    {
        $this->enviar($reserva, ReservaRegistrada::class);
    }

    public function updated(Reserva $reserva): void
    {
        // Solo interesa el cambio de estado; editar un telefono no
        // deberia generar un correo.
        if (! $reserva->wasChanged('estado')) {
            return;
        }

        $mailable = match ($reserva->estado) {
            Reserva::ESTADO_CONFIRMADA => ReservaConfirmada::class,
            Reserva::ESTADO_CANCELADA  => ReservaCancelada::class,
            default                    => null,
        };

        if ($mailable !== null) {
            $this->enviar($reserva, $mailable);
        }
    }

    /**
     * @param  class-string  $mailable
     */
    private function enviar(Reserva $reserva, string $mailable): void
    {
        if (blank($reserva->email_contacto)) {
            return;
        }

        $reserva->loadMissing('escenario');

        // Un fallo de correo no debe tumbar la operacion de negocio:
        // la reserva ya esta guardada y es lo que importa.
        try {
            Mail::to($reserva->email_contacto)->queue(new $mailable($reserva));
        } catch (\Throwable $e) {
            Log::warning('No se pudo encolar la notificacion de reserva', [
                'reserva'  => $reserva->codigo,
                'mailable' => $mailable,
                'error'    => $e->getMessage(),
            ]);
        }
    }
}

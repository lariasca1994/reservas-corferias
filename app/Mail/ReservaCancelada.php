<?php

namespace App\Mail;

/** Se envia cuando la reserva se cancela, por el cliente o por la operacion. */
class ReservaCancelada extends CorreoReserva
{
    protected function asuntoDelCorreo(): string
    {
        return 'Tu reserva fue cancelada';
    }

    protected function plantilla(): string
    {
        return 'emails.reservas.cancelada';
    }
}

<?php

namespace App\Mail;

/** Se envia cuando el area comercial confirma la reserva. */
class ReservaConfirmada extends CorreoReserva
{
    protected function asuntoDelCorreo(): string
    {
        return 'Tu reserva quedo confirmada';
    }

    protected function plantilla(): string
    {
        return 'emails.reservas.confirmada';
    }
}

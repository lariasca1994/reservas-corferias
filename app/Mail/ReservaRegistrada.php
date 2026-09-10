<?php

namespace App\Mail;

/** Se envia al registrar la solicitud, cuando queda en estado pendiente. */
class ReservaRegistrada extends CorreoReserva
{
    protected function asuntoDelCorreo(): string
    {
        return 'Recibimos tu solicitud de reserva';
    }

    protected function plantilla(): string
    {
        return 'emails.reservas.registrada';
    }
}

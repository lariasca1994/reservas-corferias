<?php

namespace App\Mail;

use App\Models\Reserva;
use App\Services\CodigoQrService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Base comun de los tres correos del ciclo de vida de una reserva.
 *
 * Implementa ShouldQueue para que el envio no bloquee la respuesta HTTP:
 * el usuario ve su comprobante de inmediato y el correo sale en segundo
 * plano. Si el servidor de correo esta caido, se reintenta sin que el
 * cliente pierda la reserva.
 */
abstract class CorreoReserva extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly Reserva $reserva,
    ) {}

    abstract protected function asuntoDelCorreo(): string;

    abstract protected function plantilla(): string;

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->asuntoDelCorreo().' · '.$this->reserva->codigo,
        );
    }

    public function content(): Content
    {
        $qr = app(CodigoQrService::class);

        return new Content(
            view: $this->plantilla(),
            with: [
                'reserva'    => $this->reserva,
                'escenario'  => $this->reserva->escenario,
                'qrPng'      => $qr->png($this->reserva),
                'urlPublica' => $qr->contenidoPara($this->reserva),
            ],
        );
    }
}

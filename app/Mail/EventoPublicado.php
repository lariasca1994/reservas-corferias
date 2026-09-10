<?php

namespace App\Mail;

use App\Models\Evento;
use App\Models\Suscriptor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Aviso de evento nuevo a la lista de distribucion.
 *
 * Cada correo lleva su propio enlace de baja, construido con el token del
 * suscriptor. Por eso el envio es individual y no una sola copia con
 * copia oculta: sin enlace personalizado no hay forma de darse de baja.
 */
class EventoPublicado extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly Evento $evento,
        public readonly Suscriptor $suscriptor,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nuevo evento: '.$this->evento->nombre,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.eventos.publicado',
            with: [
                'evento'    => $this->evento,
                'urlEvento' => route('eventos.show', $this->evento, absolute: true),
                'urlBaja'   => route('suscripciones.baja', $this->suscriptor->token_baja, absolute: true),
            ],
        );
    }
}

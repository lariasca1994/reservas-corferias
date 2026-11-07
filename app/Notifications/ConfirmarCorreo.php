<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Correo de confirmacion de la cuenta en espanol y con el nombre visible del
 * portal (reemplaza el "Verify Email Address" de Laravel). El enlace firmado
 * es el mismo que genera VerifyEmail.
 */
class ConfirmarCorreo extends VerifyEmail
{
    protected function buildMailMessage($url): MailMessage
    {
        $nombre = config('app.nombre_publico');

        return (new MailMessage)
            ->subject('Confirma tu correo · '.$nombre)
            ->greeting('Hola,')
            ->line("Gracias por crear tu cuenta en {$nombre}. Confirma tu correo para ver tu historial de reservas.")
            ->action('Confirmar correo', $url)
            ->line('Si no creaste esta cuenta, ignora este mensaje.')
            ->salutation($nombre);
    }
}

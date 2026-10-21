<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Correo de "Olvide mi contrasena" en espanol (reemplaza el de Laravel).
 * Solo texto y un boton: sin adjuntos, para no caer en filtros de spam.
 */
class RestablecerContrasena extends Notification
{
    public function __construct(private readonly string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url     = route('password.reset', ['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()]);
        $minutos = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('Restablece tu contraseña · Reservas Corferias')
            ->greeting('Hola '.$notifiable->name.',')
            ->line('Recibimos una solicitud para restablecer la contraseña de tu cuenta.')
            ->action('Restablecer contraseña', $url)
            ->line("El enlace vence en {$minutos} minutos y solo se puede usar una vez.")
            ->line('Si no fuiste tú, ignora este correo: tu contraseña no cambia.')
            ->salutation('Reservas Corferias');
    }
}

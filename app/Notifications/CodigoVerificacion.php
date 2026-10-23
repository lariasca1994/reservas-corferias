<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Codigo de 6 digitos de la verificacion en dos pasos por correo.
 */
class CodigoVerificacion extends Notification
{
    public function __construct(public readonly string $codigo, private readonly int $minutos) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tu código de acceso · Reservas Corferias')
            ->greeting('Hola '.$notifiable->name.',')
            ->line('Tu código de verificación de Reservas Corferias es:')
            ->line('**'.$this->codigo.'**')
            ->line("Vence en {$this->minutos} minutos y sirve una sola vez.")
            ->line('Si no intentaste ingresar, cambia tu contraseña.')
            ->salutation('Reservas Corferias');
    }
}

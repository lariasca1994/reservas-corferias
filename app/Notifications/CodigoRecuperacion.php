<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Codigo de 6 digitos de "Perdi mi autenticador". Llega al correo de la cuenta
 * aunque el metodo correo no este activo y solo sirve para configurar una app
 * autenticadora nueva.
 */
class CodigoRecuperacion extends Notification
{
    public function __construct(public readonly string $codigo, private readonly int $minutos) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Recupera tu acceso · Reservas Corferias')
            ->greeting('Hola '.$notifiable->name.',')
            ->line('Pediste ingresar sin tu app autenticadora. Tu código de recuperación es:')
            ->line('**'.$this->codigo.'**')
            ->line("Vence en {$this->minutos} minutos y sirve una sola vez. Después deberás configurar tu app autenticadora nueva.")
            ->line('Si no fuiste tú, cambia tu contraseña: alguien la conoce.')
            ->salutation('Reservas Corferias');
    }
}

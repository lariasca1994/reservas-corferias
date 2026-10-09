<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Envio real de diagnostico por el mailer configurado.
 *
 *   php artisan correo:probar destino@ejemplo.com
 *
 * Existe porque los correos de la aplicacion salen dentro de un try/catch
 * (una notificacion fallida no debe tumbar una reserva) y el error de
 * Brevo queda solo en el log. Aqui se ve de inmediato en la consola.
 */
class ProbarCorreo extends Command
{
    protected $signature = 'correo:probar {destino? : Direccion de prueba; por defecto el remitente}';

    protected $description = 'Envia un correo de prueba por el mailer configurado y muestra el error exacto';

    public function handle(): int
    {
        $mailer    = config('mail.default');
        $remitente = config('mail.from.address');
        $destino   = $this->argument('destino') ?: $remitente;

        $this->line("Mailer    : {$mailer}");
        $this->line('Transporte: '.get_class(Mail::mailer()->getSymfonyTransport()));
        $this->line("Remitente : {$remitente}");
        $this->line("Destino   : {$destino}");

        if ($mailer === 'brevo') {
            $this->line('Clave API : '.(filled(config('mail.mailers.brevo.key')) ? 'presente' : 'AUSENTE'));
        }

        $this->newLine();

        try {
            Mail::raw(
                'Correo de diagnostico enviado con php artisan correo:probar.',
                fn ($m) => $m->to($destino)->subject(config('app.name').' · prueba de correo'),
            );
        } catch (Throwable $e) {
            $this->error('El envio fallo.');
            $this->line(get_class($e).': '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Enviado. Debe aparecer en Brevo > Transaccional > Registros.');

        return self::SUCCESS;
    }
}

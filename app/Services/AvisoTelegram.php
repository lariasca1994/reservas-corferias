<?php

namespace App\Services;

use App\Models\Evento;
use App\Models\Reserva;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Aviso por Telegram con el mismo contenido de los correos, resumido
 * (Telegram solo admite negrita, cursiva y enlaces).
 *
 * Configuracion: TELEGRAM_BOT_TOKEN y TELEGRAM_CHAT_ID. Sin ellas no hace
 * nada, asi el entorno local y las pruebas no dependen de Telegram. Un fallo
 * nunca interrumpe la operacion de negocio ni el envio del correo.
 */
class AvisoTelegram
{
    public function reserva(Reserva $reserva, CodigoQrService $qr): void
    {
        if ($this->configurado()) {
            $this->seguro(fn () => $this->avisarReserva($reserva, $qr));
        }
    }

    public function eventoPublicado(Evento $evento, int $destinatarios, string $urlEvento): void
    {
        if ($this->configurado()) {
            $this->seguro(fn () => $this->avisarEvento($evento, $destinatarios, $urlEvento));
        }
    }

    private function configurado(): bool
    {
        return filled(config('services.telegram.token')) && filled(config('services.telegram.chat_id'));
    }

    /** Ningun fallo del aviso debe interrumpir la operacion de negocio. */
    private function seguro(callable $accion): void
    {
        try {
            $accion();
        } catch (\Throwable $e) {
            Log::warning('No se pudo armar o enviar el aviso por Telegram', ['error' => $e->getMessage()]);
        }
    }

    private function avisarReserva(Reserva $reserva, CodigoQrService $qr): void
    {
        $reserva->loadMissing('escenario');
        $dias = $reserva->diasReservados();

        $titulo = match ($reserva->estado) {
            Reserva::ESTADO_CONFIRMADA => '✅ Reserva confirmada',
            Reserva::ESTADO_CANCELADA  => '❌ Reserva cancelada',
            default                    => '🆕 Nueva solicitud de reserva',
        };

        $lineas = [
            "<b>{$titulo}</b> · Reservas Corferias",
            '<b>'.e($reserva->escenario->nombre).'</b>',
            '',
            '<b>Código:</b> '.e($reserva->codigo),
            '<b>Estado:</b> '.e($reserva->etiquetaEstado()),
            '<b>Fechas:</b> '.e($reserva->fecha_inicio->translatedFormat('j \d\e F').' al '.$reserva->fecha_fin->translatedFormat('j \d\e F \d\e Y'))." ({$dias} ".e(Str::plural('día', $dias)).')',
            '<b>Cliente:</b> '.e($reserva->nombre_contacto).($reserva->email_contacto ? ' · '.e($reserva->email_contacto) : ''),
            '<b>Valor estimado:</b> $ '.number_format($reserva->total(), 0, ',', '.'),
        ];

        if (filled($reserva->observaciones)) {
            $lineas[] = '';
            $lineas[] = '📝 <i>Observaciones de la reserva</i>';
            $lineas[] = e(mb_strimwidth($reserva->observaciones, 0, 1200, '…'));
        }

        $this->enviar(implode("\n", $lineas), $qr->contenidoPara($reserva), 'Ver comprobante');
    }

    private function avisarEvento(Evento $evento, int $destinatarios, string $urlEvento): void
    {
        $lineas = [
            '<b>📣 Evento publicado</b> · Reservas Corferias',
            '<b>'.e($evento->nombre).'</b>',
            '',
            '<b>Fechas:</b> '.e($evento->fecha_inicio->translatedFormat('j \d\e F').' al '.$evento->fecha_fin->translatedFormat('j \d\e F \d\e Y')),
            '<b>Horario:</b> '.e((string) $evento->horario),
            '<b>Aviso enviado a:</b> '.$destinatarios.' suscriptor'.($destinatarios === 1 ? '' : 'es'),
        ];

        if (filled($evento->resumen)) {
            $lineas[] = '';
            $lineas[] = e(mb_strimwidth($evento->resumen, 0, 600, '…'));
        }

        $this->enviar(implode("\n", $lineas), $urlEvento, 'Ver el evento');
    }

    private function enviar(string $texto, ?string $url, string $textoBoton): void
    {
        $token = (string) config('services.telegram.token');
        $chat  = (string) config('services.telegram.chat_id');

        try {
            $respuesta = Http::timeout(8)->post("https://api.telegram.org/bot{$token}/sendMessage", array_filter([
                'chat_id'                  => $chat,
                'text'                     => mb_substr($texto, 0, 4000),
                'parse_mode'               => 'HTML',
                'disable_web_page_preview' => true,
                'reply_markup'             => $url ? ['inline_keyboard' => [[['text' => $textoBoton, 'url' => $url]]]] : null,
            ]));

            if ($respuesta->failed()) {
                Log::warning('Telegram rechazo el aviso', ['estado' => $respuesta->status(), 'detalle' => $respuesta->body()]);
            }
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar el aviso por Telegram', ['error' => $e->getMessage()]);
        }
    }
}

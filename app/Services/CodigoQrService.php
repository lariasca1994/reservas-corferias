<?php

namespace App\Services;

use App\Models\Reserva;
use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Throwable;

/**
 * Genera el codigo QR del comprobante de reserva.
 *
 * El QR codifica la URL publica del comprobante, no los datos del cliente:
 * asi el papel impreso no expone informacion personal y el contenido que se
 * ve al escanear siempre esta actualizado.
 *
 * Se usan dos formatos segun el destino:
 *   - SVG para la pagina web, porque escala sin perder nitidez y no
 *     depende de ninguna extension de PHP.
 *   - PNG para el correo, porque la mayoria de clientes de correo bloquea
 *     o no renderiza SVG.
 */
class CodigoQrService
{
    private const TAMANO = 320;

    /** Azul profundo de la identidad visual del sitio. */
    private const COLOR = [16, 51, 59];

    /** Contenido del QR: la URL del comprobante publico. */
    public function contenidoPara(Reserva $reserva): string
    {
        return route('reservas.show', $reserva, absolute: true);
    }

    /** Codigo QR en SVG, listo para incrustar en una vista Blade. */
    public function svg(Reserva $reserva): string
    {
        $renderer = new ImageRenderer(
            $this->estilo(),
            new SvgImageBackEnd
        );

        return (new Writer($renderer))->writeString($this->contenidoPara($reserva));
    }

    /**
     * Codigo QR en PNG para adjuntar al correo.
     *
     * Requiere la extension imagick. Si no esta disponible devuelve null y
     * el correo se envia sin la imagen, en lugar de fallar por completo:
     * una notificacion sin QR sigue siendo util, una excepcion no.
     */
    public function png(Reserva $reserva): ?string
    {
        if (! extension_loaded('imagick')) {
            return null;
        }

        try {
            $renderer = new ImageRenderer(
                $this->estilo(),
                new ImagickImageBackEnd
            );

            return (new Writer($renderer))->writeString($this->contenidoPara($reserva));
        } catch (Throwable) {
            return null;
        }
    }

    private function estilo(): RendererStyle
    {
        [$r, $g, $b] = self::COLOR;

        return new RendererStyle(
            size: self::TAMANO,
            margin: 2,
            fill: Fill::uniformColor(new Rgb(255, 255, 255), new Rgb($r, $g, $b)),
        );
    }
}

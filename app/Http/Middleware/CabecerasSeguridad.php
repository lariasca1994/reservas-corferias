<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeceras de seguridad en todas las respuestas.
 *
 * Ninguna sustituye a la validacion del servidor, pero cierran vectores
 * completos con muy poco costo. La aplicacion de 2019 no enviaba ninguna.
 */
class CabecerasSeguridad
{
    public function handle(Request $request, Closure $next): Response
    {
        $respuesta = $next($request);

        // Impide que el navegador adivine el tipo de contenido: evita que
        // un archivo subido se interprete como algo distinto a lo declarado.
        $respuesta->headers->set('X-Content-Type-Options', 'nosniff');

        // Bloquea la incrustacion del sitio en un iframe ajeno, que es la
        // base del secuestro de clics.
        $respuesta->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Limita la informacion de procedencia que viaja a sitios externos.
        $respuesta->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Desactiva APIs del navegador que esta aplicacion no usa.
        $respuesta->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=()'
        );

        // Politica de contenido. Se permiten los CDN de Bootstrap y Leaflet,
        // las teselas de OpenStreetMap y los estilos en linea que usan las
        // plantillas. 'unsafe-inline' en scripts NO se habilita.
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' https://cdn.jsdelivr.net https://unpkg.com",
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://unpkg.com",
            "img-src 'self' data: https://*.tile.openstreetmap.org",
            "font-src 'self' https://cdn.jsdelivr.net",
            "connect-src 'self'",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
        ]);

        $respuesta->headers->set('Content-Security-Policy', $csp);

        // HSTS solo bajo HTTPS: enviarlo en desarrollo romperia localhost.
        if ($request->secure()) {
            $respuesta->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        return $respuesta;
    }
}

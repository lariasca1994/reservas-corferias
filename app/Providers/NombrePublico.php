<?php

namespace App\Providers;

/**
 * Nombre visible de la aplicacion. APP_NAME y MAIL_FROM_NAME vienen de las
 * variables de la nube; si llegan vacias, con el valor por defecto de Laravel
 * o con el nombre anterior del proyecto, se usa el nombre descriptivo
 * (config('app.nombre_publico')) en el encabezado y pie de los correos y en el
 * remitente. No toca la cookie de sesion ni el prefijo de cache, que ya se
 * calcularon al cargar la configuracion.
 */
class NombrePublico
{
    private const ANTERIORES = ['', 'laravel', 'example'];

    public static function aplicar(): void
    {
        $publico = (string) config('app.nombre_publico');

        foreach (['app.name', 'mail.from.name'] as $clave) {
            $valor = trim((string) config($clave));
            if (in_array(strtolower($valor), self::ANTERIORES, true) || stripos($valor, 'corferias') !== false) {
                config([$clave => $publico]);
            }
        }
    }
}

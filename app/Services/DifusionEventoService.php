<?php

namespace App\Services;

use App\Mail\EventoPublicado;
use App\Models\Evento;
use App\Models\Suscriptor;
use Illuminate\Support\Facades\Mail;

/**
 * Envio del aviso de un evento a la lista de distribucion.
 *
 * Dos decisiones deliberadas:
 *
 * 1. El envio NO se dispara al crear el evento, sino desde una accion
 *    explicita del panel. Publicar y notificar son cosas distintas: un
 *    evento puede crearse a medias, corregirse tres veces y recien
 *    entonces estar listo para anunciarse. Automatizarlo produciria
 *    correos a destiempo, y un correo no se puede deshacer.
 *
 * 2. Se procesa por lotes con chunk. Cargar en memoria una lista de
 *    decenas de miles de direcciones para recorrerla es la forma habitual
 *    de agotar la memoria del proceso.
 */
class DifusionEventoService
{
    private const LOTE = 200;

    /** @return int Cantidad de correos encolados */
    public function notificar(Evento $evento): int
    {
        $enviados = 0;

        Suscriptor::activos()
            ->orderBy('id')
            ->chunkById(self::LOTE, function ($suscriptores) use ($evento, &$enviados) {
                foreach ($suscriptores as $suscriptor) {
                    Mail::to($suscriptor->email)
                        ->queue(new EventoPublicado($evento, $suscriptor));

                    $enviados++;
                }
            });

        return $enviados;
    }

    public function totalDestinatarios(): int
    {
        return Suscriptor::activos()->count();
    }
}

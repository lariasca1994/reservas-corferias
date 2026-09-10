<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Se lanza cuando se intenta reservar un rango que ya esta ocupado.
 *
 * Existe como excepcion propia para que el controlador pueda distinguir
 * este caso de negocio de cualquier otro fallo tecnico, y responder con
 * un mensaje util en lugar de un error 500.
 */
class EscenarioNoDisponibleException extends RuntimeException
{
    public function __construct(
        public readonly string $fechaInicio,
        public readonly string $fechaFin,
    ) {
        parent::__construct(
            "El escenario no esta disponible entre {$fechaInicio} y {$fechaFin}."
        );
    }
}

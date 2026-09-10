<?php

namespace App\Services;

use App\Models\Reserva;
use InvalidArgumentException;

/**
 * Transiciones de estado de una reserva.
 *
 * Centralizar los cambios aqui evita que se escriba
 * $reserva->estado = '...' desde cualquier punto del codigo, lo que
 * terminaria produciendo estados invalidos y notificaciones inconsistentes.
 * El envio del correo lo dispara ReservaObserver al detectar el cambio.
 */
class EstadoReservaService
{
    /**
     * Transiciones permitidas: desde que estado se puede pasar a cuales.
     *
     * Una reserva cancelada es terminal: para reactivarla hay que crear una
     * nueva, porque en el intervalo las fechas pudieron ser tomadas.
     *
     * @var array<string, list<string>>
     */
    private const PERMITIDAS = [
        Reserva::ESTADO_PENDIENTE  => [Reserva::ESTADO_CONFIRMADA, Reserva::ESTADO_CANCELADA],
        Reserva::ESTADO_CONFIRMADA => [Reserva::ESTADO_CANCELADA],
        Reserva::ESTADO_CANCELADA  => [],
    ];

    public function confirmar(Reserva $reserva, ?string $observaciones = null): Reserva
    {
        return $this->cambiar($reserva, Reserva::ESTADO_CONFIRMADA, $observaciones);
    }

    public function cancelar(Reserva $reserva, ?string $motivo = null): Reserva
    {
        return $this->cambiar($reserva, Reserva::ESTADO_CANCELADA, $motivo);
    }

    public function puedeCambiar(Reserva $reserva, string $nuevoEstado): bool
    {
        return in_array($nuevoEstado, self::PERMITIDAS[$reserva->estado] ?? [], true);
    }

    private function cambiar(Reserva $reserva, string $nuevoEstado, ?string $observaciones): Reserva
    {
        if (! $this->puedeCambiar($reserva, $nuevoEstado)) {
            throw new InvalidArgumentException(
                "No se puede pasar de {$reserva->estado} a {$nuevoEstado}."
            );
        }

        $reserva->estado = $nuevoEstado;

        if (filled($observaciones)) {
            $reserva->observaciones = $observaciones;
        }

        $reserva->save();

        return $reserva;
    }
}

<?php

namespace App\Services;

use App\Exceptions\EscenarioNoDisponibleException;
use App\Models\Escenario;
use App\Models\Reserva;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Reglas de ocupacion de escenarios.
 *
 * Toda la logica de disponibilidad vive aqui y no en el controlador, para
 * poder probarla sin levantar una peticion HTTP y para que exista un unico
 * lugar donde se decide si un rango esta libre.
 *
 * En la version de 2019 esta responsabilidad estaba dentro de
 * ReservationController, comparando contra cuatro arreglos literales, y la
 * condicion de cruce estaba mal planteada: solo detectaba el rango
 * totalmente contenido dentro de uno ocupado.
 */
class DisponibilidadService
{
    /**
     * Indica si el escenario esta libre en todo el rango solicitado.
     *
     * @param  int|null  $ignorarReservaId  Reserva a excluir de la comprobacion,
     *                                      util al editar una reserva existente.
     */
    public function estaDisponible(
        Escenario $escenario,
        string $fechaInicio,
        string $fechaFin,
        ?int $ignorarReservaId = null,
    ): bool {
        return ! $this->consultaDeCruces($escenario, $fechaInicio, $fechaFin, $ignorarReservaId)->exists();
    }

    /**
     * Reservas que se cruzan con el rango, para poder explicarle al usuario
     * exactamente que fechas estan tomadas.
     *
     * @return Collection<int, Reserva>
     */
    public function crucesCon(
        Escenario $escenario,
        string $fechaInicio,
        string $fechaFin,
    ): Collection {
        return $this->consultaDeCruces($escenario, $fechaInicio, $fechaFin)
            ->orderBy('fecha_inicio')
            ->get();
    }

    /**
     * Rangos ocupados de un escenario a futuro, para pintar el calendario.
     *
     * @return Collection<int, array{inicio: string, fin: string}>
     */
    public function rangosOcupados(Escenario $escenario, int $diasHaciaAdelante = 180): Collection
    {
        $hasta = now()->addDays($diasHaciaAdelante)->toDateString();

        return $escenario->reservas()
            ->queBloquean()
            ->whereDate('fecha_fin', '>=', now()->toDateString())
            ->whereDate('fecha_inicio', '<=', $hasta)
            ->orderBy('fecha_inicio')
            ->get()
            ->map(fn (Reserva $reserva) => [
                'inicio' => $reserva->fecha_inicio->toDateString(),
                'fin'    => $reserva->fecha_fin->toDateString(),
            ]);
    }

    /**
     * Crea la reserva verificando disponibilidad dentro de una transaccion.
     *
     * La comprobacion previa en el formulario mejora la experiencia, pero no
     * basta: entre esa comprobacion y el guardado puede colarse otra reserva.
     * Por eso aqui se vuelve a verificar con la fila del escenario bloqueada,
     * de modo que dos peticiones simultaneas no puedan ocupar el mismo rango.
     *
     * @param  array<string, mixed>  $datos
     *
     * @throws EscenarioNoDisponibleException
     */
    public function reservar(Escenario $escenario, array $datos): Reserva
    {
        return DB::transaction(function () use ($escenario, $datos) {
            // Bloqueo pesimista sobre el escenario: serializa las reservas
            // concurrentes del mismo escenario sin afectar a los demas.
            Escenario::whereKey($escenario->getKey())->lockForUpdate()->first();

            $inicio = $datos['fecha_inicio'];
            $fin    = $datos['fecha_fin'];

            if (! $this->estaDisponible($escenario, $inicio, $fin)) {
                throw new EscenarioNoDisponibleException($inicio, $fin);
            }

            return $escenario->reservas()->create([
                'user_id'           => $datos['user_id'] ?? null,
                'nombre_contacto'   => $datos['nombre_contacto'],
                'email_contacto'    => $datos['email_contacto'],
                'telefono_contacto' => $datos['telefono_contacto'],
                'fecha_inicio'      => $inicio,
                'fecha_fin'         => $fin,
                'estado'            => Reserva::ESTADO_PENDIENTE,
                'observaciones'     => $datos['observaciones'] ?? null,
            ]);
        });
    }

    /**
     * Consulta base de cruces. Dos rangos [a1,a2] y [b1,b2] se solapan si y
     * solo si a1 <= b2 y a2 >= b1.
     */
    private function consultaDeCruces(
        Escenario $escenario,
        string $fechaInicio,
        string $fechaFin,
        ?int $ignorarReservaId = null,
    ) {
        return $escenario->reservas()
            ->queBloquean()
            ->queSeCruzanCon($fechaInicio, $fechaFin)
            ->when($ignorarReservaId, fn ($q) => $q->whereKeyNot($ignorarReservaId));
    }
}

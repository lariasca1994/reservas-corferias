<?php

namespace App\Http\Controllers;

use App\Exceptions\EscenarioNoDisponibleException;
use App\Http\Requests\GuardarReservaRequest;
use App\Models\Escenario;
use App\Models\Reserva;
use App\Services\DisponibilidadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReservaController extends Controller
{
    public function __construct(
        private readonly DisponibilidadService $disponibilidad,
    ) {}

    /** Formulario de reserva de un escenario. */
    public function create(Escenario $escenario): View
    {
        abort_unless($escenario->activo, 404);

        $escenario->load('imagenes');

        return view('reservas.create', [
            'escenario' => $escenario,
            'ocupados'  => $this->disponibilidad->rangosOcupados($escenario),
        ]);
    }

    /**
     * Registra la reserva.
     *
     * La validacion ya comprobo disponibilidad, pero el servicio la vuelve a
     * verificar dentro de la transaccion. Si entre ambos momentos otra
     * peticion ocupo el rango, se captura la excepcion de dominio y se
     * devuelve al formulario con el mensaje correspondiente.
     */
    public function store(GuardarReservaRequest $request, Escenario $escenario): RedirectResponse
    {
        abort_unless($escenario->activo, 404);

        $datos            = $request->validated();
        $datos['user_id'] = $request->user()?->id;

        try {
            $reserva = $this->disponibilidad->reservar($escenario, $datos);
        } catch (EscenarioNoDisponibleException $e) {
            return back()
                ->withInput()
                ->withErrors([
                    'fecha_inicio' => 'Alguien reservó estas fechas mientras completabas el formulario. Elige un rango distinto.',
                ]);
        }

        return redirect()
            ->route('reservas.show', $reserva)
            ->with('exito', 'Tu solicitud de reserva quedó registrada.');
    }

    /** Comprobante publico de la reserva, accesible por su codigo. */
    public function show(Reserva $reserva): View
    {
        $reserva->load('escenario');

        return view('reservas.show', [
            'reserva' => $reserva,
        ]);
    }
}

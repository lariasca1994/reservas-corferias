<?php

namespace App\Http\Controllers;

use App\Models\Escenario;
use App\Services\DisponibilidadService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class EscenarioController extends Controller
{
    public function __construct(
        private readonly DisponibilidadService $disponibilidad,
    ) {}

    public function index(): View
    {
        return view('escenarios.index', [
            'escenarios' => Escenario::activos()->orderBy('capacidad')->get(),
        ]);
    }

    /**
     * Detalle de un escenario. El binding resuelve por slug, asi que a
     * diferencia de la version original el parametro de la ruta si determina
     * que se muestra.
     */
    public function show(Escenario $escenario): View
    {
        abort_unless($escenario->activo, 404);

        $escenario->load(['caracteristicas', 'imagenes']);

        return view('escenarios.show', [
            'escenario' => $escenario,
            'ocupados'  => $this->disponibilidad->rangosOcupados($escenario),
        ]);
    }

    /** Disponibilidad en JSON, para que el formulario avise antes de enviar. */
    public function disponibilidad(Escenario $escenario): JsonResponse
    {
        return response()->json([
            'escenario' => $escenario->slug,
            'ocupados'  => $this->disponibilidad->rangosOcupados($escenario),
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use App\Services\EstadoReservaService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * Historial de reservas del cliente autenticado.
 *
 * Da sentido al rol 'cliente': sin esta seccion el rol existiria en la
 * base de datos sin habilitar nada, que es una incoherencia de diseno.
 */
class MisReservasController extends Controller
{
    public function __construct(
        private readonly EstadoReservaService $estados,
    ) {}

    public function index(Request $request): View
    {
        $reservas = $request->user()
            ->reservas()
            ->with('escenario')
            ->orderByDesc('fecha_inicio')
            ->paginate(10);

        return view('reservas.mias', ['reservas' => $reservas]);
    }

    /**
     * Cancelacion por parte del propio cliente.
     *
     * Se verifica la pertenencia antes de actuar: sin esa comprobacion,
     * cualquiera con sesion iniciada podria cancelar la reserva de otro
     * cambiando el codigo en la URL. Es la vulnerabilidad conocida como
     * referencia directa insegura a objetos.
     */
    public function cancelar(Request $request, Reserva $reserva): RedirectResponse
    {
        abort_unless($reserva->user_id === $request->user()->id, 403);

        $datos = $request->validate([
            'motivo' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->estados->cancelar(
                $reserva,
                $datos['motivo'] ?? 'Cancelada por el cliente desde su cuenta.'
            );
        } catch (InvalidArgumentException $e) {
            return back()->with('aviso', $e->getMessage());
        }

        return back()->with('exito', "La reserva {$reserva->codigo} quedó cancelada.");
    }
}

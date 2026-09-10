<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Escenario;
use App\Models\Reserva;
use App\Services\EstadoReservaService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

class ReservaAdminController extends Controller
{
    public function __construct(
        private readonly EstadoReservaService $estados,
    ) {}

    public function index(Request $request): View
    {
        $reservas = Reserva::with('escenario')
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->string('estado')))
            ->when($request->filled('escenario'), fn ($q) => $q->where('escenario_id', $request->integer('escenario')))
            ->when($request->filled('buscar'), function ($q) use ($request) {
                $texto = $request->string('buscar')->toString();
                $q->where(fn ($sub) => $sub
                    ->where('codigo', 'like', "%{$texto}%")
                    ->orWhere('nombre_contacto', 'like', "%{$texto}%")
                    ->orWhere('email_contacto', 'like', "%{$texto}%"));
            })
            ->orderByDesc('fecha_inicio')
            ->paginate(20)
            ->withQueryString();

        return view('admin.reservas.index', [
            'reservas'   => $reservas,
            'escenarios' => Escenario::orderBy('nombre')->get(),
            'estados'    => [
                Reserva::ESTADO_PENDIENTE,
                Reserva::ESTADO_CONFIRMADA,
                Reserva::ESTADO_CANCELADA,
            ],
        ]);
    }

    public function show(Reserva $reserva): View
    {
        $reserva->load(['escenario', 'usuario']);

        return view('admin.reservas.show', ['reserva' => $reserva]);
    }

    /** Confirma la reserva y dispara el correo con el codigo QR. */
    public function confirmar(Reserva $reserva): RedirectResponse
    {
        try {
            $this->estados->confirmar($reserva);
        } catch (InvalidArgumentException $e) {
            return back()->with('aviso', $e->getMessage());
        }

        return back()->with('exito', "La reserva {$reserva->codigo} quedó confirmada y se notificó al cliente.");
    }

    public function cancelar(Request $request, Reserva $reserva): RedirectResponse
    {
        $datos = $request->validate([
            'motivo' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->estados->cancelar($reserva, $datos['motivo'] ?? null);
        } catch (InvalidArgumentException $e) {
            return back()->with('aviso', $e->getMessage());
        }

        return back()->with('exito', "La reserva {$reserva->codigo} quedó cancelada y se notificó al cliente.");
    }
}

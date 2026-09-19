<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Escenario;
use App\Models\Evento;
use App\Models\Reserva;
use Illuminate\View\View;

class PanelController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.panel', [
            'pendientes'        => Reserva::where('estado', Reserva::ESTADO_PENDIENTE)->count(),
            'confirmadas'       => Reserva::where('estado', Reserva::ESTADO_CONFIRMADA)->count(),
            'escenariosActivos' => Escenario::activos()->count(),
            'eventosVigentes'   => Evento::vigentes()->count(),
            'ultimas'           => Reserva::with('escenario')->latest()->take(8)->get(),
        ]);
    }
}

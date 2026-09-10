<?php

namespace App\Http\Controllers;

use App\Models\Escenario;
use App\Models\Evento;
use Illuminate\View\View;

class InicioController extends Controller
{
    public function __invoke(): View
    {
        return view('inicio', [
            'eventosDestacados' => Evento::vigentes()->destacados()->take(3)->get(),
            'escenarios'        => Escenario::activos()->orderBy('capacidad')->take(4)->get(),
        ]);
    }
}

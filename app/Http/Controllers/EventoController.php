<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use Illuminate\View\View;

class EventoController extends Controller
{
    public function index(): View
    {
        return view('eventos.index', [
            'eventos' => Evento::vigentes()->with('escenario')->paginate(9),
        ]);
    }

    /**
     * Detalle del evento.
     *
     * El controlador de 2019 recibia un {id} y lo ignoraba: devolvia siempre
     * el mismo evento con texto de relleno. Aqui el binding por slug carga
     * el registro real o responde 404.
     */
    public function show(Evento $evento): View
    {
        $evento->load('escenario');

        return view('eventos.show', [
            'evento' => $evento,
        ]);
    }
}

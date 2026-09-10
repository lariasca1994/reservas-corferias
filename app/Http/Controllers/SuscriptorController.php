<?php

namespace App\Http\Controllers;

use App\Models\Suscriptor;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SuscriptorController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'email'  => ['required', 'email:rfc', 'max:150'],
            'nombre' => ['nullable', 'string', 'max:120'],
        ], [
            'email.email' => 'El correo electrónico no tiene un formato válido.',
        ]);

        // Si ya existia y se habia dado de baja, se reactiva en lugar de
        // fallar por correo duplicado.
        Suscriptor::updateOrCreate(
            ['email' => $datos['email']],
            [
                'nombre'  => $datos['nombre'] ?? null,
                'baja_en' => null,
            ]
        );

        // La respuesta es la misma exista o no el correo: revelar cuales
        // estan registrados permitiria enumerar la lista.
        return back()->with(
            'exito',
            'Listo. Te avisaremos cuando publiquemos eventos nuevos.'
        );
    }

    public function baja(string $token): View
    {
        $suscriptor = Suscriptor::where('token_baja', $token)->first();

        if ($suscriptor && ! $suscriptor->estaDadoDeBaja()) {
            $suscriptor->darDeBaja();
        }

        // Se responde igual con token valido o invalido, por la misma razon.
        return view('suscripciones.baja', [
            'email' => $suscriptor?->email,
        ]);
    }
}

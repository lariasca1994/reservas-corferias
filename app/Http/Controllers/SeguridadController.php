<?php

namespace App\Http\Controllers;

use App\Services\DosPasos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Mi cuenta → Seguridad: agregar el metodo de verificacion que falta y elegir
 * cual se pide por defecto.
 */
class SeguridadController extends Controller
{
    public function __construct(private readonly DosPasos $dosPasos) {}

    private function vista(Request $request, array $extra = []): View
    {
        $u = $request->user();

        return view('cuenta.seguridad', [
            'metodos'   => DosPasos::METODOS,
            'activos'   => $this->dosPasos->activos($u),
            'preferido' => $this->dosPasos->preferido($u),
            'agregando' => $extra['agregando'] ?? null,
            'app'       => $extra['app'] ?? null,
            'email'     => $u->email,
        ]);
    }

    public function show(Request $request): View
    {
        return $this->vista($request);
    }

    public function agregar(Request $request): View|RedirectResponse
    {
        $datos = $request->validate(['metodo' => ['required', Rule::in(['totp', 'correo'])]]);
        $u     = $request->user();
        if (in_array($datos['metodo'], $this->dosPasos->activos($u), true)) {
            return redirect()->route('cuenta.seguridad');
        }
        if ($datos['metodo'] === 'totp') {
            return $this->vista($request, ['agregando' => 'totp', 'app' => $this->dosPasos->iniciarTotp($u)]);
        }
        if (! $this->dosPasos->enviarCodigo($u)) {
            return redirect()->route('cuenta.seguridad')->withErrors(['codigo' => 'Pediste varios códigos seguidos. Espera unos minutos.']);
        }

        return $this->vista($request, ['agregando' => 'correo']);
    }

    public function activar(Request $request): RedirectResponse
    {
        $datos    = $request->validate(['metodo' => ['required', Rule::in(['totp', 'correo'])], 'codigo' => ['required', 'string', 'max:8']]);
        $u        = $request->user();
        $activado = $datos['metodo'] === 'totp'
            ? $this->dosPasos->activarTotp($u, $datos['codigo'])
            : $this->dosPasos->activarCorreo($u, $datos['codigo']);
        if (! $activado) {
            return redirect()->route('cuenta.seguridad')->withErrors(['codigo' => 'El código no es correcto o ya venció. Vuelve a intentarlo.']);
        }

        return redirect()->route('cuenta.seguridad')->with('exito', DosPasos::METODOS[$datos['metodo']].' quedó activada como segundo paso.');
    }

    public function preferido(Request $request): RedirectResponse
    {
        $datos = $request->validate(['metodo' => ['required', Rule::in(['totp', 'correo'])]]);
        $this->dosPasos->elegirPreferido($request->user(), $datos['metodo']);

        return redirect()->route('cuenta.seguridad');
    }
}

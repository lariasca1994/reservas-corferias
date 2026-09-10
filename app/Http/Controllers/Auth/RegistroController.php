<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegistroRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Registro de clientes.
 *
 * En 2019 existia una vista de registro que no persistia nada: el
 * formulario carecia de action y de method, y no habia tabla de usuarios.
 */
class RegistroController extends Controller
{
    public function create(): View
    {
        return view('auth.registro');
    }

    public function store(RegistroRequest $request): RedirectResponse
    {
        // El rol se fija aqui, nunca se toma de la peticion.
        $usuario = User::create([
            ...$request->validated(),
            'rol'    => User::ROL_CLIENTE,
            'activo' => true,
        ]);

        // La asociacion de reservas previas NO ocurre aqui: se dispara al
        // verificar el correo (ver AsociarReservasPrevias). Hacerlo en el
        // registro permitiria apropiarse del historial de otra persona
        // simplemente registrandose con su direccion.
        //event(new Registered($usuario));

        Auth::login($usuario);
        $request->session()->regenerate();

        return redirect()
            ->route('verification.notice')
            ->with('exito', 'Tu cuenta quedó creada. Revisa tu correo para confirmarla.');
    }
}

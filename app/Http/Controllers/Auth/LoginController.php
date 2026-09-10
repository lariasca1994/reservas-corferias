<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Inicio y cierre de sesion.
 *
 * La version de 2019 tenia el formulario, pero no hacia nada: sin action,
 * sin method, sin token CSRF y sin comprobacion alguna en el servidor.
 * Aqui se usa el guard de Laravel con limitacion de intentos y
 * regeneracion de sesion para evitar fijacion de sesion.
 */
class LoginController extends Controller
{
    private const MAX_INTENTOS = 5;

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required'    => 'Ingresa tu correo electrónico.',
            'email.email'       => 'El correo electrónico no tiene un formato válido.',
            'password.required' => 'Ingresa tu contraseña.',
        ]);

        $llave = 'login:'.strtolower($datos['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($llave, self::MAX_INTENTOS)) {
            $segundos = RateLimiter::availableIn($llave);

            throw ValidationException::withMessages([
                'email' => "Demasiados intentos. Vuelve a intentarlo en {$segundos} segundos.",
            ]);
        }

        if (! Auth::attempt($datos, $request->boolean('recordarme'))) {
            RateLimiter::hit($llave, 60);

            throw ValidationException::withMessages([
                'email' => 'Las credenciales no coinciden con nuestros registros.',
            ]);
        }

        if (! $request->user()->activo) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Esta cuenta está desactivada.',
            ]);
        }

        RateLimiter::clear($llave);
        $request->session()->regenerate();

        return redirect()->intended(
            $request->user()->puedeGestionar()
                ? route('admin.panel')
                : route('mis-reservas.index')
        );
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('inicio');
    }
}

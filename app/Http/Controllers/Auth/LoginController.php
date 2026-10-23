<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
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

        // Solo se comprueba la contraseña: la sesion se abre tras el segundo paso.
        if (! Auth::validate($datos)) {
            RateLimiter::hit($llave, 60);

            throw ValidationException::withMessages([
                'email' => 'Las credenciales no coinciden con nuestros registros.',
            ]);
        }

        $usuario = User::where('email', $datos['email'])->firstOrFail();

        if (! $usuario->activo) {
            throw ValidationException::withMessages([
                'email' => 'Esta cuenta está desactivada.',
            ]);
        }

        RateLimiter::clear($llave);

        return VerificacionController::pendiente(
            $request,
            $usuario,
            $usuario->puedeGestionar() ? route('admin.panel') : route('mis-reservas.index'),
            $request->boolean('recordarme')
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

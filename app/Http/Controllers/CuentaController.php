<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Cambio de contrasena de la cuenta con sesion iniciada (clientes, operadores
 * y administradores). Exige la contrasena actual y cierra las demas sesiones.
 */
class CuentaController extends Controller
{
    public function contrasena(): View
    {
        return view('cuenta.contrasena');
    }

    public function cambiarContrasena(Request $request): RedirectResponse
    {
        $request->validate([
            'actual'   => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:actual', Password::min(10)->letters()->numbers()],
        ], [
            'actual.required'         => 'Escribe tu contraseña actual.',
            'actual.current_password' => 'La contraseña actual no es correcta.',
            'password.required'       => 'Escribe la nueva contraseña.',
            'password.confirmed'      => 'La confirmación de la contraseña no coincide.',
            'password.different'      => 'La nueva contraseña debe ser distinta de la actual.',
        ], ['password' => 'contraseña']);

        $request->user()->forceFill(['password' => Hash::make($request->input('password'))])->save();
        Auth::logoutOtherDevices($request->input('password'));

        return back()->with('exito', 'Tu contraseña quedó actualizada.');
    }
}

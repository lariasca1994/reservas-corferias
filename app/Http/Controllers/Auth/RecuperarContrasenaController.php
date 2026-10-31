<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auditoria;
use App\Services\DosPasos;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password as Broker;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Recuperacion de cuenta: "Olvide mi contrasena".
 *
 * Usa el broker de contrasenas de Laravel: el token es de un solo uso,
 * se guarda solo como hash (tabla password_reset_tokens) y vence segun
 * config/auth.php. La respuesta al pedir el enlace es siempre la misma,
 * exista o no la cuenta, para no revelar que correos estan registrados.
 */
class RecuperarContrasenaController extends Controller
{
    private const RESPUESTA_GENERICA = 'Si el correo pertenece a una cuenta, te enviamos un enlace para restablecer la contraseña.';

    public function olvide(): View
    {
        return view('auth.olvide');
    }

    public function enviarEnlace(Request $request): RedirectResponse
    {
        $request->validate(
            ['email' => ['required', 'email']],
            ['email.required' => 'Ingresa tu correo electrónico.', 'email.email' => 'El correo electrónico no tiene un formato válido.'],
        );

        // Las cuentas desactivadas no reciben enlace (tampoco podrian ingresar).
        $usuario = User::where('email', strtolower(trim($request->input('email'))))->first();
        if ($usuario?->activo) {
            Broker::sendResetLink(['email' => $usuario->email]);
            app(Auditoria::class)->registrar(Auditoria::RECUPERACION, $usuario);
        }

        return back()->with('exito', self::RESPUESTA_GENERICA);
    }

    public function restablecer(Request $request, string $token): View
    {
        return view('auth.restablecer', ['token' => $token, 'email' => $request->query('email', '')]);
    }

    public function guardar(Request $request): RedirectResponse
    {
        $request->validate([
            'token'    => ['required'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()],
        ], [
            'email.required'     => 'Ingresa tu correo electrónico.',
            'password.required'  => 'Escribe la nueva contraseña.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ], ['password' => 'contraseña']);

        $estado = Broker::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $usuario, string $clave) {
                $usuario->forceFill([
                    'password'       => Hash::make($clave),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($usuario));
                app(DosPasos::class)->claveRestablecida($usuario);
            }
        );

        if ($estado !== Broker::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'El enlace no es válido o ya venció. Pide uno nuevo.']);
        }

        return redirect()->route('login')->with('exito', 'Tu contraseña quedó actualizada. Ya puedes ingresar.');
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auditoria;
use App\Services\DosPasos;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    /**
     * Paso 2. El correo llega en el enlace (?email=); se comprueba contra el token,
     * se guarda en la sesion y se quita de la direccion. El formulario solo pide la
     * contrasena nueva: nadie puede cambiar a mano la cuenta a la que se aplica.
     */
    public function restablecer(Request $request, string $token): View|RedirectResponse
    {
        if ($request->filled('email')) {
            $usuario = User::where('email', strtolower(trim((string) $request->query('email'))))->first();
            $valido  = $usuario?->activo && Broker::broker()->tokenExists($usuario, $token);
            $valido
                ? $request->session()->put('restablecer', ['huella' => hash('sha256', $token), 'email' => $usuario->email])
                : $request->session()->forget('restablecer');

            return redirect()->route('password.reset', ['token' => $token]);
        }

        return view('auth.restablecer', ['token' => $token, 'valido' => $this->correoDelEnlace($request, $token) !== null]);
    }

    /** Correo guardado al abrir el enlace, solo si corresponde a este mismo token. */
    private function correoDelEnlace(Request $request, string $token): ?string
    {
        $r = $request->session()->get('restablecer');

        return $r && hash_equals($r['huella'], hash('sha256', $token)) ? $r['email'] : null;
    }

    public function guardar(Request $request): RedirectResponse
    {
        $request->validate([
            'token'    => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()],
        ], [
            'password.required'  => 'Escribe la nueva contraseña.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ], ['password' => 'contraseña']);

        $token  = (string) $request->input('token');
        $email  = $this->correoDelEnlace($request, $token);
        $estado = $email === null ? Broker::INVALID_TOKEN : Broker::reset(
            ['email' => $email, 'token' => $token, 'password' => $request->input('password')],
            function (User $usuario, string $clave) {
                $usuario->forceFill([
                    'password'       => Hash::make($clave),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($usuario));
                $this->cerrarSesiones($usuario);
                // La verificacion en dos pasos no cambia: el proximo ingreso la sigue pidiendo.
                app(DosPasos::class)->claveRestablecida($usuario);
            }
        );

        if ($estado !== Broker::PASSWORD_RESET) {
            return back()->withErrors(['enlace' => 'El enlace no es válido o ya venció. Pide uno nuevo.']);
        }

        $request->session()->forget('restablecer');

        return redirect()->route('login')->with('exito', 'Tu contraseña quedó actualizada. Ya puedes ingresar.');
    }

    /**
     * Cierra las sesiones abiertas de la cuenta. El remember_token nuevo invalida
     * "Recordarme"; AuthenticateSession (bootstrap/app.php) cierra las sesiones que
     * guardaron la contrasena anterior, sea cual sea el driver; con el driver de base
     * de datos ademas se borran sus filas.
     */
    private function cerrarSesiones(User $usuario): void
    {
        if (config('session.driver') === 'database') {
            DB::connection(config('session.connection'))
                ->table(config('session.table', 'sessions'))
                ->where('user_id', $usuario->id)
                ->delete();
        }
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\DosPasos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Segundo paso del ingreso (obligatorio). La contraseña ya se comprobo en
 * LoginController o RegistroController: la cuenta queda pendiente en la sesion
 * (10 minutos) y solo se inicia sesion al confirmar el codigo.
 */
class VerificacionController extends Controller
{
    private const MINUTOS = 10;

    public function __construct(private readonly DosPasos $dosPasos) {}

    /** Deja la cuenta pendiente del segundo paso y la lleva a la verificacion. */
    public static function pendiente(Request $request, User $usuario, string $destino, bool $recordar = false): RedirectResponse
    {
        $request->session()->regenerate();
        $request->session()->put('mfa', [
            'usuario'  => $usuario->id,
            'hasta'    => time() + self::MINUTOS * 60,
            'recordar' => $recordar,
            'destino'  => $destino,
        ]);
        $dosPasos = app(DosPasos::class);
        if ($dosPasos->preferido($usuario) === 'correo') {
            $dosPasos->enviarCodigo($usuario);
        }

        return redirect()->route('verificacion');
    }

    private function usuario(Request $request): ?User
    {
        $m = $request->session()->get('mfa');
        if (! $m || $m['hasta'] < time()) {
            return null;
        }
        $u = User::find($m['usuario']);

        return $u && $u->activo ? $u : null;
    }

    private function vencido(): RedirectResponse
    {
        return redirect()->route('login')->withErrors(['email' => 'El paso de verificación venció; vuelve a ingresar.']);
    }

    private function vista(User $u, array $extra = []): View
    {
        $metodo = $extra['metodo'] ?? null;
        $metodo = in_array($metodo, $this->dosPasos->activos($u), true) ? $metodo : $this->dosPasos->preferido($u);
        $otro   = collect($this->dosPasos->activos($u))->first(fn ($m) => $m !== $metodo);

        return view('auth.verificacion', [
            'modo'                => $extra['modo'] ?? ($this->dosPasos->configurado($u) ? 'verificar' : 'elegir'),
            'metodo'              => $extra['metodo'] ?? $metodo,
            'otro'                => $otro,
            'app'                 => $extra['app'] ?? null,
            'email'               => $u->email,
            'minutosRecuperacion' => DosPasos::RECUPERACION_MIN,
        ]);
    }

    /** Estado de "Perdi mi autenticador" en la sesion: null, 'codigo' (enviado) o 'verificado'. */
    private function recuperacion(Request $request): ?string
    {
        return $request->session()->get('mfa.recuperacion');
    }

    public function show(Request $request): View|RedirectResponse
    {
        $u = $this->usuario($request);
        if (! $u) {
            return $this->vencido();
        }
        // Elegir un metodo de forma explicita cancela la recuperacion en curso.
        if ($request->query('metodo')) {
            $request->session()->forget('mfa.recuperacion');
        }

        return match ($this->recuperacion($request)) {
            'codigo'     => $this->vista($u, ['modo' => 'recuperar', 'metodo' => 'recuperacion']),
            'verificado' => $this->vista($u, ['modo' => 'reemplazar', 'metodo' => 'totp', 'app' => $this->dosPasos->iniciarTotp($u)]),
            default      => $this->vista($u, ['metodo' => $request->query('metodo')]),
        };
    }

    /** "Perdi mi autenticador": envia el codigo de recuperacion al correo de la cuenta. */
    public function perdiAutenticador(Request $request): RedirectResponse
    {
        $u = $this->usuario($request);
        if (! $u) {
            return $this->vencido();
        }
        if ($this->recuperacion($request) === 'verificado') {
            return redirect()->route('verificacion');
        }
        if (! $this->dosPasos->enviarCodigoRecuperacion($u)) {
            return redirect()->route('verificacion')->withErrors(['codigo' => 'Pediste varios códigos seguidos. Espera unos minutos.']);
        }
        $request->session()->put('mfa.recuperacion', 'codigo');

        return redirect()->route('verificacion')->with('exito', "Te enviamos un código de recuperación a {$u->email}.");
    }

    /** Comprueba el codigo de recuperacion; si es correcto, pide configurar la app nueva. */
    public function recuperar(Request $request): RedirectResponse
    {
        $datos = $request->validate(['codigo' => ['required', 'string', 'max:8']]);
        $u     = $this->usuario($request);
        if (! $u) {
            return $this->vencido();
        }
        if ($this->recuperacion($request) !== 'codigo') {
            return redirect()->route('verificacion');
        }
        if (! $this->dosPasos->verificarRecuperacion($u, $datos['codigo'])) {
            return redirect()->route('verificacion')->withErrors(['codigo' => 'El código no es correcto o ya venció.']);
        }
        // Tiempo suficiente para instalar la app nueva y escanear el QR.
        $request->session()->put('mfa.recuperacion', 'verificado');
        $request->session()->put('mfa.hasta', time() + DosPasos::RECUPERACION_MIN * 60);

        return redirect()->route('verificacion')->with('exito', 'Código correcto. Configura tu app autenticadora nueva para terminar de ingresar.');
    }

    public function verificar(Request $request): View|RedirectResponse
    {
        $datos = $request->validate(['metodo' => ['required', Rule::in(['totp', 'correo'])], 'codigo' => ['required', 'string', 'max:8']]);
        $u     = $this->usuario($request);
        if (! $u) {
            return $this->vencido();
        }
        if (! $this->dosPasos->verificar($u, $datos['metodo'], $datos['codigo'])) {
            return back()->withInput(['metodo' => $datos['metodo']])->withErrors(['codigo' => 'El código no es correcto o ya venció.']);
        }

        return $this->entrar($request, $u);
    }

    public function reenviar(Request $request): RedirectResponse
    {
        $u = $this->usuario($request);
        if (! $u) {
            return $this->vencido();
        }
        if (! in_array('correo', $this->dosPasos->activos($u), true) || ! $this->dosPasos->enviarCodigo($u)) {
            return redirect()->route('verificacion', ['metodo' => 'correo'])->withErrors(['codigo' => 'Pediste varios códigos seguidos. Espera unos minutos.']);
        }

        return redirect()->route('verificacion', ['metodo' => 'correo'])->with('exito', "Te enviamos un código a {$u->email}.");
    }

    public function configurar(Request $request): View|RedirectResponse
    {
        $datos = $request->validate(['metodo' => ['required', Rule::in(['totp', 'correo'])]]);
        $u     = $this->usuario($request);
        if (! $u) {
            return $this->vencido();
        }
        if ($this->dosPasos->configurado($u)) {
            return redirect()->route('verificacion');
        }
        if ($datos['metodo'] === 'totp') {
            return $this->vista($u, ['modo' => 'activar', 'metodo' => 'totp', 'app' => $this->dosPasos->iniciarTotp($u)]);
        }
        if (! $this->dosPasos->enviarCodigo($u)) {
            return redirect()->route('verificacion')->withErrors(['codigo' => 'Pediste varios códigos seguidos. Espera unos minutos.']);
        }

        return $this->vista($u, ['modo' => 'activar', 'metodo' => 'correo']);
    }

    public function activar(Request $request): View|RedirectResponse
    {
        $datos = $request->validate(['metodo' => ['required', Rule::in(['totp', 'correo'])], 'codigo' => ['required', 'string', 'max:8']]);
        $u     = $this->usuario($request);
        if (! $u) {
            return $this->vencido();
        }
        // Tras "Perdi mi autenticador" solo vale reemplazar la app autenticadora.
        if ($this->recuperacion($request) === 'verificado') {
            if ($datos['metodo'] !== 'totp' || ! $this->dosPasos->reemplazarTotp($u, $datos['codigo'])) {
                return redirect()->route('verificacion')->withErrors(['codigo' => 'El código no es correcto. Escanea el código QR nuevo e inténtalo otra vez.']);
            }

            return $this->entrar($request, $u);
        }
        if ($this->dosPasos->configurado($u)) {
            return redirect()->route('verificacion');
        }
        $activado = $datos['metodo'] === 'totp'
            ? $this->dosPasos->activarTotp($u, $datos['codigo'])
            : $this->dosPasos->activarCorreo($u, $datos['codigo']);
        if (! $activado) {
            return redirect()->route('verificacion')->withErrors(['codigo' => 'El código no es correcto o ya venció. Vuelve a elegir el método.']);
        }

        return $this->entrar($request, $u);
    }

    private function entrar(Request $request, User $u): RedirectResponse
    {
        $m = $request->session()->pull('mfa');
        Auth::login($u, (bool) ($m['recordar'] ?? false));
        $request->session()->regenerate();

        return redirect()->intended($m['destino'] ?? route('inicio'));
    }
}

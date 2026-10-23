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
            'modo'   => $extra['modo'] ?? ($this->dosPasos->configurado($u) ? 'verificar' : 'elegir'),
            'metodo' => $extra['metodo'] ?? $metodo,
            'otro'   => $otro,
            'app'    => $extra['app'] ?? null,
            'email'  => $u->email,
        ]);
    }

    public function show(Request $request): View|RedirectResponse
    {
        $u = $this->usuario($request);

        return $u ? $this->vista($u, ['metodo' => $request->query('metodo')]) : $this->vencido();
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

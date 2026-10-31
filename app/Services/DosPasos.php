<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\CodigoVerificacion;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Crypt;

/**
 * Verificacion en dos pasos (obligatoria). Cada cuenta usa al menos uno de dos metodos:
 *
 *  - App autenticadora (TOTP, RFC 6238): la clave se guarda cifrada con la APP_KEY
 *    (Crypt); un mismo codigo no se acepta dos veces y 5 errados la bloquean 15 minutos.
 *  - Codigo por correo: 6 digitos que vencen en 10 minutos; solo se guarda su huella
 *    (HMAC con la APP_KEY), admite 5 intentos y se pueden pedir 3 cada 10 minutos.
 *
 * Todo vive en la columna JSON "mfa" de users (oculta en las respuestas).
 */
class DosPasos
{
    public const METODOS = ['totp' => 'App autenticadora', 'correo' => 'Código por correo'];

    private const PASO = 30;

    private const CODIGO_MIN = 10;

    private const MAX_INTENTOS = 5;

    private const MAX_ENVIOS = 3;

    private const BLOQUEO_SEG = 15 * 60;

    private const ALFABETO = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function estado(User $u): array
    {
        return $u->mfa ?? [];
    }

    /** @return list<string> */
    public function activos(User $u): array
    {
        $m = $this->estado($u);

        return array_values(array_filter(['totp', 'correo'], fn ($x) => ! empty($m[$x])));
    }

    public function configurado(User $u): bool
    {
        return $this->activos($u) !== [];
    }

    public function preferido(User $u): ?string
    {
        $activos = $this->activos($u);
        $elegido = $this->estado($u)['preferido'] ?? null;

        return in_array($elegido, $activos, true) ? $elegido : ($activos[0] ?? null);
    }

    private function guardar(User $u, array $cambios): void
    {
        $u->mfa = array_merge($this->estado($u), $cambios);
        $u->save();
    }

    private function huella(string $codigo): string
    {
        return hash_hmac('sha256', $codigo, (string) config('app.key'));
    }

    private static function limpiar(?string $codigo): string
    {
        return preg_replace('/\D/', '', (string) $codigo);
    }

    public static function totp(string $secreto, int $paso): string
    {
        $bits = '';
        foreach (str_split(rtrim($secreto, '=')) as $c) {
            $bits .= str_pad(decbin(strpos(self::ALFABETO, $c)), 5, '0', STR_PAD_LEFT);
        }
        $clave = '';
        foreach (str_split(substr($bits, 0, intdiv(strlen($bits), 8) * 8), 8) as $b) {
            $clave .= chr(bindec($b));
        }
        $h = hash_hmac('sha1', pack('J', $paso), $clave, true);
        $o = ord($h[19]) & 15;
        $n = ((ord($h[$o]) & 0x7F) << 24) | (ord($h[$o + 1]) << 16) | (ord($h[$o + 2]) << 8) | ord($h[$o + 3]);

        return str_pad((string) ($n % 1000000), 6, '0', STR_PAD_LEFT);
    }

    // ─── app autenticadora ───

    /** @return array{qr: string, secreto: string} */
    public function iniciarTotp(User $u): array
    {
        $bits = '';
        foreach (str_split(random_bytes(20)) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }
        $secreto = implode('', array_map(fn ($g) => self::ALFABETO[bindec(str_pad($g, 5, '0'))], str_split($bits, 5)));
        $this->guardar($u, ['totp_pendiente' => Crypt::encryptString($secreto)]);

        $emisor = 'Reservas Corferias';
        $uri    = 'otpauth://totp/'.rawurlencode($emisor).':'.rawurlencode($u->email)
            .'?secret='.$secreto.'&issuer='.rawurlencode($emisor).'&digits=6&period='.self::PASO;
        $svg = (new Writer(new ImageRenderer(new RendererStyle(190, 1), new SvgImageBackEnd)))->writeString($uri);

        return ['qr' => $svg, 'secreto' => $secreto];
    }

    private function verificarTotp(User $u, ?string $cifrado, string $codigo): bool
    {
        if (! $cifrado || strlen($codigo) !== 6) {
            return false;
        }
        $secreto = Crypt::decryptString($cifrado);
        $actual  = intdiv(time(), self::PASO);
        $ultimo  = $this->estado($u)['ultimo_paso'] ?? 0;
        foreach ([$actual - 1, $actual, $actual + 1] as $paso) {
            if ($paso > $ultimo && hash_equals(self::totp($secreto, $paso), $codigo)) {
                $this->guardar($u, ['ultimo_paso' => $paso]);

                return true;
            }
        }

        return false;
    }

    private function activarTotpSinRegistro(User $u, ?string $codigo): bool
    {
        $pendiente = $this->estado($u)['totp_pendiente'] ?? null;
        if (! $this->verificarTotp($u, $pendiente, self::limpiar($codigo))) {
            return false;
        }
        $cambios = ['totp' => $pendiente, 'totp_pendiente' => null];
        if (! $this->configurado($u)) {
            $cambios['preferido'] = 'totp';
        }
        $this->guardar($u, $cambios);

        return true;
    }

    // ─── código por correo ───

    public function enviarCodigo(User $u): bool
    {
        $ahora  = time();
        $envios = array_values(array_filter($this->estado($u)['envios'] ?? [], fn ($t) => $ahora - $t < self::CODIGO_MIN * 60));
        if (count($envios) >= self::MAX_ENVIOS) {
            return false;
        }
        $codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $this->guardar($u, [
            'codigo' => ['huella' => $this->huella($codigo), 'expira' => $ahora + self::CODIGO_MIN * 60, 'intentos' => 0],
            'envios' => [...$envios, $ahora],
        ]);
        $u->notify(new CodigoVerificacion($codigo, self::CODIGO_MIN));

        return true;
    }

    private function verificarCorreo(User $u, string $codigo): bool
    {
        $g = $this->estado($u)['codigo'] ?? null;
        if (! $g || ($g['intentos'] ?? 0) >= self::MAX_INTENTOS || $g['expira'] < time()) {
            return false;
        }
        if (! hash_equals($g['huella'], $this->huella($codigo))) {
            $this->guardar($u, ['codigo' => [...$g, 'intentos' => ($g['intentos'] ?? 0) + 1]]);

            return false;
        }
        $this->guardar($u, ['codigo' => null]);

        return true;
    }

    private function activarCorreoSinRegistro(User $u, ?string $codigo): bool
    {
        if (! $this->verificarCorreo($u, self::limpiar($codigo))) {
            return false;
        }
        $cambios = ['correo' => true];
        if (! $this->configurado($u)) {
            $cambios['preferido'] = 'correo';
        }
        $this->guardar($u, $cambios);

        return true;
    }

    // ─── verificación ───

    private function verificarMetodo(User $u, string $metodo, ?string $codigo): bool
    {
        $codigo = self::limpiar($codigo);
        if (! in_array($metodo, $this->activos($u), true)) {
            return false;
        }
        if ($metodo === 'correo') {
            return $this->verificarCorreo($u, $codigo);
        }
        // Sin este freno, con la contraseña bastaria probar codigos hasta acertar.
        $e = $this->estado($u);
        if (($e['bloqueo_hasta'] ?? 0) > time()) {
            return false;
        }
        if ($this->verificarTotp($u, $e['totp'] ?? null, $codigo)) {
            if (! empty($e['fallos'])) {
                $this->guardar($u, ['fallos' => 0]);
            }

            return true;
        }
        $fallos = ($e['fallos'] ?? 0) + 1;
        $this->guardar($u, $fallos >= self::MAX_INTENTOS
            ? ['fallos' => 0, 'bloqueo_hasta' => time() + self::BLOQUEO_SEG]
            : ['fallos' => $fallos]);

        return false;
    }

    public function elegirPreferido(User $u, string $metodo): bool
    {
        if (! in_array($metodo, $this->activos($u), true)) {
            return false;
        }
        $this->guardar($u, ['preferido' => $metodo]);

        return true;
    }

    // ─── auditoría y bloqueo por contraseña errada ───

    private const MAX_FALLOS_CLAVE = 5;

    private const BLOQUEO_CLAVE_SEG = 15 * 60;

    public const MENSAJE_BLOQUEO = 'Demasiados intentos fallidos. Espera unos minutos e inténtalo de nuevo.';

    public function verificar(User $u, string $metodo, ?string $codigo): bool
    {
        $ok = $this->verificarMetodo($u, $metodo, $codigo);
        app(Auditoria::class)->registrar($ok ? Auditoria::INGRESO : Auditoria::CODIGO_ERRADO, $u);

        return $ok;
    }

    public function activarTotp(User $u, ?string $codigo): bool
    {
        return $this->conRegistroDeActivacion($u, $this->activarTotpSinRegistro($u, $codigo));
    }

    public function activarCorreo(User $u, ?string $codigo): bool
    {
        return $this->conRegistroDeActivacion($u, $this->activarCorreoSinRegistro($u, $codigo));
    }

    private function conRegistroDeActivacion(User $u, bool $ok): bool
    {
        if ($ok) {
            app(Auditoria::class)->registrar(Auditoria::METODO_ACTIVADO, $u);
        }

        return $ok;
    }

    public function claveBloqueada(User $u): bool
    {
        return ($this->estado($u)['bloqueo_clave_hasta'] ?? 0) > time();
    }

    /** Suma una contraseña errada; la quinta seguida bloquea la cuenta un tiempo. */
    public function falloDeClave(User $u): void
    {
        $auditoria = app(Auditoria::class);
        $auditoria->registrar(Auditoria::CLAVE_ERRADA, $u);
        $fallos = ($this->estado($u)['fallos_clave'] ?? 0) + 1;
        if ($fallos >= self::MAX_FALLOS_CLAVE) {
            $this->guardar($u, ['fallos_clave' => 0, 'bloqueo_clave_hasta' => time() + self::BLOQUEO_CLAVE_SEG]);
            $auditoria->registrar(Auditoria::CUENTA_BLOQUEADA, $u);
        } else {
            $this->guardar($u, ['fallos_clave' => $fallos]);
        }
    }

    public function claveCorrecta(User $u): void
    {
        if (! empty($this->estado($u)['fallos_clave'])) {
            $this->guardar($u, ['fallos_clave' => 0]);
        }
    }

    /** Contraseña nueva (recuperación o cambio): se levanta el bloqueo y queda en la auditoría. */
    public function claveRestablecida(User $u): void
    {
        $this->guardar($u, ['fallos_clave' => 0, 'bloqueo_clave_hasta' => 0]);
        app(Auditoria::class)->registrar(Auditoria::CLAVE_CAMBIADA, $u);
    }
}

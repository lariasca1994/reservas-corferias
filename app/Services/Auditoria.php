<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Registro de eventos de seguridad de las cuentas: que paso, a que cuenta y cuando; nunca
 * contrasenas, codigos ni datos del equipo. Cada registro borra los de mas de 15 dias.
 * Si la base no responde, el evento se pierde pero el ingreso sigue.
 */
class Auditoria
{
    public const INGRESO = 'ingreso';

    public const CLAVE_ERRADA = 'clave_errada';

    public const CODIGO_ERRADO = 'codigo_errado';

    public const CUENTA_BLOQUEADA = 'cuenta_bloqueada';

    public const CLAVE_CAMBIADA = 'clave_cambiada';

    public const RECUPERACION = 'recuperacion_solicitada';

    public const METODO_ACTIVADO = 'metodo_activado';

    private const RETENCION_DIAS = 15;

    public function registrar(string $tipo, User $u): void
    {
        try {
            DB::table('eventos_seguridad')->where('fecha', '<', now()->subDays(self::RETENCION_DIAS))->delete();
            DB::table('eventos_seguridad')->insert(['tipo' => $tipo, 'user_id' => $u->id, 'fecha' => now()]);
        } catch (Throwable) {
            Log::warning("No se pudo registrar el evento de seguridad {$tipo}");
        }
    }
}

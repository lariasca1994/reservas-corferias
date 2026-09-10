<?php

namespace App\Listeners;

use App\Models\Reserva;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Log;

/**
 * Asocia a la cuenta las reservas hechas antes de registrarse.
 *
 * Se ejecuta al VERIFICAR el correo, nunca al registrarse. La diferencia
 * es de seguridad, no de estilo: sin la verificacion, cualquiera podria
 * registrarse con la direccion de otra persona y quedarse con su
 * historial de reservas, incluidos su telefono y la posibilidad de
 * cancelarlas.
 */
class AsociarReservasPrevias
{
    public function handle(Verified $event): void
    {
        $usuario = $event->user;

        $asociadas = Reserva::whereNull('user_id')
            ->where('email_contacto', $usuario->email)
            ->update(['user_id' => $usuario->id]);

        if ($asociadas > 0) {
            Log::info('Reservas previas asociadas tras verificar el correo', [
                'usuario'  => $usuario->id,
                'cantidad' => $asociadas,
            ]);
        }
    }
}

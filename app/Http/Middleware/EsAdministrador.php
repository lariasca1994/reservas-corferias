<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reserva ciertas acciones al administrador.
 *
 * La gestion de usuarios queda fuera del alcance del operador: quien
 * atiende reservas no deberia poder crearse cuentas ni cambiar roles.
 */
class EsAdministrador
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        abort_unless(
            $usuario !== null && $usuario->activo && $usuario->esAdministrador(),
            403,
            'Esta sección está reservada para administradores.'
        );

        return $next($request);
    }
}

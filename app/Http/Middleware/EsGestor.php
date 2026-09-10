<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restringe el panel a usuarios con rol de gestion y cuenta activa.
 *
 * Se comprueba tambien 'activo' y no solo el rol: desactivar una cuenta
 * debe cortar el acceso de inmediato, sin tener que cambiarle el rol ni
 * borrar el registro, que es lo que se necesita cuando alguien sale de la
 * organizacion.
 */
class EsGestor
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        abort_unless($usuario !== null && $usuario->puedeGestionar(), 403,
            'No tienes permisos para acceder al panel de gestión.');

        return $next($request);
    }
}

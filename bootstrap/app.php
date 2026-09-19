<?php

use App\Http\Middleware\CabecerasSeguridad;
use App\Http\Middleware\EsAdministrador;
use App\Http\Middleware\EsGestor;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Confiar en el proxy de Azure Container Apps para que Laravel
        // reconozca correctamente el esquema (https) y el host originales
        // de la petición (necesario para que las URLs firmadas, como la
        // de verificación de correo, no fallen con "Invalid signature").
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO,
        );

        // Cabeceras de seguridad en todas las respuestas web.
        $middleware->web(append: [
            CabecerasSeguridad::class,
        ]);

        $middleware->alias([
            'gestor'        => EsGestor::class,
            'administrador' => EsAdministrador::class,
        ]);

        // Ruta a la que se redirige a los invitados que intentan entrar
        // a una zona protegida.
        $middleware->redirectGuestsTo(fn () => route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
<?php

use App\Http\Middleware\CabecerasSeguridad;
use App\Http\Middleware\EsAdministrador;
use App\Http\Middleware\EsGestor;
use Illuminate\Database\QueryException;
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
        // Base pausada o sin cuota gratuita del mes: página de mantenimiento
        // (503) en lugar de un error genérico. Los demás errores siguen igual.
        $exceptions->render(function (Throwable $e, Request $request) {
            $base        = $e instanceof QueryException || $e instanceof PDOException;
            $mensaje     = $e->getMessage();
            $sinConexion = str_contains($mensaje, 'SQLSTATE[08')
                || str_contains($mensaje, 'free amount allowance')
                || str_contains($mensaje, 'is not currently available')
                || str_contains($mensaje, 'Login timeout expired')
                || str_contains($mensaje, 'TCP Provider');
            if (! $base || ! $sinConexion) {
                return null;
            }

            return $request->expectsJson()
                ? response()->json(['mensaje' => 'Servicio en mantenimiento. Intenta más tarde.'], 503)
                : response()->view('errors.base-no-disponible', [], 503)->header('Retry-After', '3600');
        });
    })->create();

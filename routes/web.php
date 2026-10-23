<?php

use App\Http\Controllers\Admin\EscenarioAdminController;
use App\Http\Controllers\Admin\EventoAdminController;
use App\Http\Controllers\Admin\PanelController;
use App\Http\Controllers\Admin\ReservaAdminController;
use App\Http\Controllers\Admin\UsuarioAdminController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RecuperarContrasenaController;
use App\Http\Controllers\Auth\RegistroController;
use App\Http\Controllers\Auth\VerificacionController;
use App\Http\Controllers\CuentaController;
use App\Http\Controllers\EscenarioController;
use App\Http\Controllers\EventoController;
use App\Http\Controllers\InicioController;
use App\Http\Controllers\MisReservasController;
use App\Http\Controllers\ReservaController;
use App\Http\Controllers\SeguridadController;
use App\Http\Controllers\SuscriptorController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas publicas
|--------------------------------------------------------------------------
|
| Todas las rutas con parametro usan binding de modelo: {escenario} resuelve
| por slug y {reserva} por codigo. En la version de 2019 los parametros
| {type} e {id} llegaban al controlador y se descartaban, de modo que todas
| las URLs mostraban el mismo contenido.
|
*/

Route::get('/', InicioController::class)->name('inicio');

Route::get('/escenarios', [EscenarioController::class, 'index'])->name('escenarios.index');
Route::get('/escenarios/{escenario}', [EscenarioController::class, 'show'])->name('escenarios.show');
Route::get('/escenarios/{escenario}/disponibilidad', [EscenarioController::class, 'disponibilidad'])
    ->name('escenarios.disponibilidad');

Route::get('/escenarios/{escenario}/reservar', [ReservaController::class, 'create'])->name('reservas.create');
Route::post('/escenarios/{escenario}/reservar', [ReservaController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('reservas.store');

Route::get('/reservas/{reserva}', [ReservaController::class, 'show'])
    ->middleware('throttle:30,1')
    ->name('reservas.show');

Route::get('/eventos', [EventoController::class, 'index'])->name('eventos.index');
Route::get('/eventos/{evento}', [EventoController::class, 'show'])->name('eventos.show');

/*
|--------------------------------------------------------------------------
| Autenticacion
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/ingresar', [LoginController::class, 'create'])->name('login');
    Route::post('/ingresar', [LoginController::class, 'store'])->middleware('throttle:20,1');

    Route::get('/registro', [RegistroController::class, 'create'])->name('registro');
    Route::post('/registro', [RegistroController::class, 'store'])->middleware('throttle:10,1');

    // Recuperacion de cuenta: enlace de un solo uso por correo.
    Route::get('/contrasena/olvide', [RecuperarContrasenaController::class, 'olvide'])->name('password.request');
    Route::post('/contrasena/olvide', [RecuperarContrasenaController::class, 'enviarEnlace'])
        ->middleware('throttle:5,1')
        ->name('password.email');
    Route::get('/contrasena/restablecer/{token}', [RecuperarContrasenaController::class, 'restablecer'])
        ->name('password.reset');
    Route::post('/contrasena/restablecer', [RecuperarContrasenaController::class, 'guardar'])
        ->middleware('throttle:10,1')
        ->name('password.update');
});

// Segundo paso del ingreso: la cuenta queda pendiente en la sesion (sin iniciarla).
Route::middleware('guest')->group(function () {
    Route::get('/verificacion', [VerificacionController::class, 'show'])->name('verificacion');
    Route::post('/verificacion', [VerificacionController::class, 'verificar'])->middleware('throttle:20,1')->name('verificacion.verificar');
    Route::post('/verificacion/correo', [VerificacionController::class, 'reenviar'])->middleware('throttle:5,1')->name('verificacion.correo');
    Route::post('/verificacion/configurar', [VerificacionController::class, 'configurar'])->middleware('throttle:10,1')->name('verificacion.configurar');
    Route::post('/verificacion/activar', [VerificacionController::class, 'activar'])->middleware('throttle:20,1')->name('verificacion.activar');
});

Route::post('/salir', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Zona del cliente
|--------------------------------------------------------------------------
|
| Da contenido al rol 'cliente': historial propio y cancelacion de sus
| propias reservas. La pertenencia se verifica en el controlador.
|
*/

Route::middleware('auth')->group(function () {

    // --- Cambio de contrasena (cualquier rol) ------------------------
    Route::get('/cuenta/contrasena', [CuentaController::class, 'contrasena'])->name('cuenta.contrasena');
    Route::put('/cuenta/contrasena', [CuentaController::class, 'cambiarContrasena'])
        ->middleware('throttle:10,1')
        ->name('cuenta.contrasena.actualizar');

    // --- Verificacion en dos pasos (cualquier rol) ---------------------
    Route::get('/cuenta/seguridad', [SeguridadController::class, 'show'])->name('cuenta.seguridad');
    Route::post('/cuenta/seguridad/agregar', [SeguridadController::class, 'agregar'])->middleware('throttle:10,1')->name('cuenta.seguridad.agregar');
    Route::post('/cuenta/seguridad/activar', [SeguridadController::class, 'activar'])->middleware('throttle:20,1')->name('cuenta.seguridad.activar');
    Route::post('/cuenta/seguridad/preferido', [SeguridadController::class, 'preferido'])->name('cuenta.seguridad.preferido');

    // --- Verificacion de correo -------------------------------------
    Route::get('/correo/verificar', fn () => view('auth.verificar'))
        ->name('verification.notice');

    Route::get('/correo/verificar/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();

        return redirect()->route('mis-reservas.index')
            ->with('exito', 'Tu correo quedó confirmado.');
    })->middleware('signed')->name('verification.verify');

    Route::post('/correo/reenviar', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();

        return back()->with('exito', 'Te reenviamos el enlace de confirmación.');
    })->middleware('throttle:6,1')->name('verification.send');

    // --- Zona del cliente -------------------------------------------
    // Exige correo verificado: el historial puede contener reservas
    // hechas antes del registro con esa misma direccion.
    Route::middleware('verified')->group(function () {
        Route::get('/mis-reservas', [MisReservasController::class, 'index'])->name('mis-reservas.index');
        Route::patch('/mis-reservas/{reserva}/cancelar', [MisReservasController::class, 'cancelar'])
            ->name('mis-reservas.cancelar');
    });
});

/*
|--------------------------------------------------------------------------
| Lista de distribucion
|--------------------------------------------------------------------------
*/

Route::post('/suscripciones', [SuscriptorController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('suscripciones.store');

Route::get('/suscripciones/baja/{token}', [SuscriptorController::class, 'baja'])
    ->name('suscripciones.baja');

/*
|--------------------------------------------------------------------------
| Panel de gestion
|--------------------------------------------------------------------------
|
| 'gestor' admite administradores y operadores. La gestion de cuentas
| exige ademas 'administrador': quien atiende reservas no deberia poder
| crear usuarios ni cambiar roles.
|
*/

Route::prefix('panel')
    ->name('admin.')
    ->middleware(['auth', 'gestor'])
    ->group(function () {

        Route::get('/', PanelController::class)->name('panel');

        Route::get('reservas', [ReservaAdminController::class, 'index'])->name('reservas.index');
        Route::get('reservas/{reserva}', [ReservaAdminController::class, 'show'])->name('reservas.show');
        Route::patch('reservas/{reserva}/confirmar', [ReservaAdminController::class, 'confirmar'])->name('reservas.confirmar');
        Route::patch('reservas/{reserva}/cancelar', [ReservaAdminController::class, 'cancelar'])->name('reservas.cancelar');

        Route::resource('escenarios', EscenarioAdminController::class)->except('show');
        Route::resource('eventos', EventoAdminController::class)->except('show');
        Route::post('eventos/{evento}/notificar', [EventoAdminController::class, 'notificar'])
            ->name('eventos.notificar');

        Route::middleware('administrador')->group(function () {
            Route::resource('usuarios', UsuarioAdminController::class)->except('show');
        });
    });

<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\RestablecerContrasena;
use App\Services\Auditoria;
use App\Services\DosPasos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Cambio de contrasena con sesion iniciada y recuperacion de cuenta por correo.
 */
class ContrasenaTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(array $datos = []): User
    {
        return User::factory()->create(array_merge([
            'email'    => 'ana@ejemplo.com',
            'password' => Hash::make('clave-actual-2026'),
            'rol'      => User::ROL_CLIENTE,
            'activo'   => true,
        ], $datos));
    }

    #[Test]
    public function el_login_enlaza_a_recuperar_la_contrasena(): void
    {
        $this->get(route('login'))->assertOk()->assertSee(route('password.request'), false);
    }

    #[Test]
    public function pedir_el_enlace_envia_el_correo_en_espanol_a_cuentas_activas(): void
    {
        Notification::fake();
        $usuario = $this->usuario();

        $this->post(route('password.email'), ['email' => 'ana@ejemplo.com'])
            ->assertSessionHas('exito');

        Notification::assertSentTo($usuario, RestablecerContrasena::class, function ($n) use ($usuario) {
            $correo = $n->toMail($usuario);

            return $correo->subject === 'Restablece tu contraseña · Reservas Corferias'
                && str_contains($correo->actionUrl, '/contrasena/restablecer/');
        });
    }

    #[Test]
    public function la_respuesta_es_igual_si_el_correo_no_existe_o_la_cuenta_esta_inactiva(): void
    {
        Notification::fake();
        $inactivo = $this->usuario(['email' => 'inactivo@ejemplo.com', 'activo' => false]);

        $existe = $this->post(route('password.email'), ['email' => 'nadie@ejemplo.com']);
        $this->post(route('password.email'), ['email' => 'inactivo@ejemplo.com'])
            ->assertSessionHas('exito', $existe->getSession()->get('exito'));

        Notification::assertNothingSent();
        $this->assertNotNull($inactivo);
    }

    #[Test]
    public function el_enlace_restablece_la_contrasena_una_sola_vez(): void
    {
        $usuario = $this->usuario();
        $token   = Password::createToken($usuario);
        $datos   = ['token' => $token, 'password' => 'nueva-clave-2026', 'password_confirmation' => 'nueva-clave-2026'];

        $this->abrirEnlace($token, 'ana@ejemplo.com');
        $this->post(route('password.update'), $datos)->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('nueva-clave-2026', $usuario->fresh()->password));

        $this->abrirEnlace($token, 'ana@ejemplo.com');
        $this->post(route('password.update'), [...$datos, 'password' => 'otra-clave-2026', 'password_confirmation' => 'otra-clave-2026'])
            ->assertSessionHasErrors('enlace');
        $this->assertTrue(Hash::check('nueva-clave-2026', $usuario->fresh()->password));
    }

    /** Abre el enlace del correo: el correo se guarda en la sesion y sale de la direccion. */
    private function abrirEnlace(string $token, string $email): void
    {
        $this->get(route('password.reset', ['token' => $token, 'email' => $email]))
            ->assertRedirect(route('password.reset', ['token' => $token]));
    }

    #[Test]
    public function el_paso_dos_solo_pide_la_nueva_contrasena(): void
    {
        $usuario = $this->usuario();
        $token   = Password::createToken($usuario);
        $this->abrirEnlace($token, 'ana@ejemplo.com');

        $this->get(route('password.reset', ['token' => $token]))->assertOk()
            ->assertSee('name="password"', false)
            ->assertSee('name="password_confirmation"', false)
            ->assertDontSee('id="email"', false)
            ->assertDontSee('ana@ejemplo.com');
    }

    #[Test]
    public function el_correo_no_se_puede_cambiar_por_otra_cuenta(): void
    {
        $ana   = $this->usuario();
        $otro  = $this->usuario(['email' => 'otro@ejemplo.com']);
        $token = Password::createToken($ana);

        // El token de Ana con el correo de otra cuenta no abre el formulario.
        $this->abrirEnlace($token, 'otro@ejemplo.com');
        $this->get(route('password.reset', ['token' => $token]))->assertOk()
            ->assertSee('El enlace no es válido o ya venció')
            ->assertDontSee('name="password"', false);

        // Y un campo email enviado a mano se ignora.
        $this->post(route('password.update'), [
            'token' => $token, 'email' => 'otro@ejemplo.com', 'password' => 'nueva-clave-2026', 'password_confirmation' => 'nueva-clave-2026',
        ])->assertSessionHasErrors('enlace');
        $this->assertTrue(Hash::check('clave-actual-2026', $ana->fresh()->password));
        $this->assertTrue(Hash::check('clave-actual-2026', $otro->fresh()->password));
    }

    #[Test]
    public function un_enlace_vencido_no_sirve(): void
    {
        $usuario = $this->usuario();
        $token   = Password::createToken($usuario);
        $this->travel(61)->minutes();

        $this->abrirEnlace($token, 'ana@ejemplo.com');
        $this->get(route('password.reset', ['token' => $token]))->assertSee('Pedir un enlace nuevo');
    }

    #[Test]
    public function restablecer_cierra_las_sesiones_y_mantiene_los_dos_pasos(): void
    {
        config(['session.driver' => 'database']);
        $usuario = $this->usuario(['mfa' => ['correo' => true, 'preferido' => 'correo']]);
        DB::table('sessions')->insert(['id' => 'sesion-abierta', 'user_id' => $usuario->id, 'payload' => '', 'last_activity' => time()]);
        $recordar = $usuario->remember_token;
        $token    = Password::createToken($usuario);

        $this->abrirEnlace($token, 'ana@ejemplo.com');
        $this->post(route('password.update'), ['token' => $token, 'password' => 'nueva-clave-2026', 'password_confirmation' => 'nueva-clave-2026'])
            ->assertRedirect(route('login'));

        $usuario->refresh();
        $this->assertSame(0, DB::table('sessions')->where('user_id', $usuario->id)->count());
        $this->assertNotSame($recordar, $usuario->remember_token);
        $this->assertSame(['correo'], app(DosPasos::class)->activos($usuario));
        $this->assertDatabaseHas('eventos_seguridad', ['tipo' => Auditoria::CLAVE_CAMBIADA, 'user_id' => $usuario->id]);
    }

    #[Test]
    public function una_sesion_abierta_se_cierra_al_cambiar_la_contrasena(): void
    {
        $usuario = $this->usuario();
        $this->actingAs($usuario)->get(route('cuenta.contrasena'))->assertOk();

        // Otro equipo cambia la contrasena: esta sesion guardo la anterior.
        $usuario->forceFill(['password' => Hash::make('nueva-clave-2026')])->save();
        $this->get(route('cuenta.contrasena'))->assertRedirect(route('login'));
    }

    #[Test]
    public function cambiar_la_contrasena_exige_la_actual(): void
    {
        $usuario = $this->usuario();

        $this->actingAs($usuario)->put(route('cuenta.contrasena.actualizar'), [
            'actual' => 'equivocada', 'password' => 'nueva-clave-2026', 'password_confirmation' => 'nueva-clave-2026',
        ])->assertSessionHasErrors('actual');

        $this->actingAs($usuario)->put(route('cuenta.contrasena.actualizar'), [
            'actual' => 'clave-actual-2026', 'password' => 'nueva-clave-2026', 'password_confirmation' => 'nueva-clave-2026',
        ])->assertSessionHas('exito');

        $this->assertTrue(Hash::check('nueva-clave-2026', $usuario->fresh()->password));
    }

    #[Test]
    public function cambiar_la_contrasena_requiere_sesion(): void
    {
        $this->get(route('cuenta.contrasena'))->assertRedirect(route('login'));
    }
}

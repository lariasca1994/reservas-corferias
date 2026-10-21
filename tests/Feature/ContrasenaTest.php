<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\RestablecerContrasena;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $datos   = ['token' => $token, 'email' => 'ana@ejemplo.com', 'password' => 'nueva-clave-2026', 'password_confirmation' => 'nueva-clave-2026'];

        $this->post(route('password.update'), $datos)->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('nueva-clave-2026', $usuario->fresh()->password));

        $this->post(route('password.update'), [...$datos, 'password' => 'otra-clave-2026', 'password_confirmation' => 'otra-clave-2026'])
            ->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('nueva-clave-2026', $usuario->fresh()->password));
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

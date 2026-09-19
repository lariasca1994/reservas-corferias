<?php

namespace Tests\Feature;

use App\Mail\EventoPublicado;
use App\Models\Escenario;
use App\Models\Evento;
use App\Models\Reserva;
use App\Models\Suscriptor;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SeguridadTest extends TestCase
{
    use RefreshDatabase;

    // ─────────────────────────────── contraseñas ───────────────────

    #[Test]
    public function las_contrasenas_se_guardan_cifradas(): void
    {
        $usuario = User::factory()->create(['password' => 'clave-en-texto-plano-2026']);

        $this->assertNotSame('clave-en-texto-plano-2026', $usuario->password);
        $this->assertTrue(Hash::check('clave-en-texto-plano-2026', $usuario->password));
        $this->assertStringStartsWith('$2y$', $usuario->password);
    }

    #[Test]
    public function la_contrasena_nunca_viaja_en_la_serializacion_del_modelo(): void
    {
        $usuario = User::factory()->create();

        $this->assertArrayNotHasKey('password', $usuario->toArray());
        $this->assertArrayNotHasKey('remember_token', $usuario->toArray());
    }

    // ───────────────────── apropiación de historial ────────────────

    #[Test]
    public function registrarse_con_el_correo_ajeno_no_da_acceso_a_sus_reservas(): void
    {
        $victima = Reserva::factory()->for(Escenario::factory())->create([
            'email_contacto' => 'victima@ejemplo.com',
            'user_id'        => null,
        ]);

        $this->post(route('registro'), [
            'name'                  => 'Atacante',
            'email'                 => 'victima@ejemplo.com',
            'password'              => 'clave-segura-2026',
            'password_confirmation' => 'clave-segura-2026',
        ]);

        // Sin verificar el correo, la reserva sigue sin dueño.
        $this->assertNull($victima->fresh()->user_id);
    }

    #[Test]
    public function las_reservas_previas_se_asocian_solo_al_verificar_el_correo(): void
    {
        $reserva = Reserva::factory()->for(Escenario::factory())->create([
            'email_contacto' => 'ana@ejemplo.com',
            'user_id'        => null,
        ]);

        $usuario = User::factory()->unverified()->create(['email' => 'ana@ejemplo.com']);

        $this->assertNull($reserva->fresh()->user_id);

        event(new Verified($usuario));

        $this->assertSame($usuario->id, $reserva->fresh()->user_id);
    }

    #[Test]
    public function una_cuenta_sin_verificar_no_entra_al_historial(): void
    {
        $this->actingAs(User::factory()->unverified()->create())
            ->get(route('mis-reservas.index'))
            ->assertRedirect(route('verification.notice'));
    }

    // ─────────────────────────── cabeceras ─────────────────────────

    #[Test]
    public function las_respuestas_llevan_cabeceras_de_seguridad(): void
    {
        $respuesta = $this->get(route('inicio'));

        $respuesta->assertHeader('X-Content-Type-Options', 'nosniff');
        $respuesta->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $respuesta->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertNotNull($respuesta->headers->get('Content-Security-Policy'));
    }

    // ────────────────────────────── CSRF ───────────────────────────

    #[Test]
    public function los_formularios_publicos_llevan_token_csrf(): void
    {
        $escenario = Escenario::factory()->create();

        $this->get(route('reservas.create', $escenario))
            ->assertSee('name="_token"', false);
    }

    // ───────────────────────── suscripciones ───────────────────────

    #[Test]
    public function la_suscripcion_no_revela_si_un_correo_ya_estaba_registrado(): void
    {
        Suscriptor::create(['email' => 'ya@ejemplo.com']);

        $primera = $this->post(route('suscripciones.store'), ['email' => 'nuevo@ejemplo.com']);
        $segunda = $this->post(route('suscripciones.store'), ['email' => 'ya@ejemplo.com']);

        $this->assertSame(
            session()->get('exito'),
            $segunda->getSession()->get('exito'),
            'El mensaje debe ser identico para no permitir enumerar la lista.'
        );

        $primera->assertSessionHasNoErrors();
        $segunda->assertSessionHasNoErrors();
    }

    #[Test]
    public function darse_de_baja_desactiva_los_envios(): void
    {
        $suscriptor = Suscriptor::create(['email' => 'ana@ejemplo.com']);

        $this->get(route('suscripciones.baja', $suscriptor->token_baja))->assertOk();

        $this->assertNotNull($suscriptor->fresh()->baja_en);
        $this->assertSame(0, Suscriptor::activos()->count());
    }

    #[Test]
    public function un_token_de_baja_invalido_no_revela_informacion(): void
    {
        $suscriptor = Suscriptor::create(['email' => 'ana@ejemplo.com']);

        $this->get(route('suscripciones.baja', 'token-que-no-existe'))
            ->assertOk()
            ->assertDontSee('ana@ejemplo.com');
    }

    // ─────────────────────────── difusión ──────────────────────────

    #[Test]
    public function el_aviso_de_evento_llega_solo_a_los_suscriptores_activos(): void
    {
        Mail::fake();

        Suscriptor::create(['email' => 'uno@ejemplo.com']);
        Suscriptor::create(['email' => 'dos@ejemplo.com']);
        Suscriptor::create(['email' => 'baja@ejemplo.com'])->darDeBaja();

        $evento = Evento::factory()->create();

        $this->actingAs(User::factory()->administrador()->create())
            ->post(route('admin.eventos.notificar', $evento))
            ->assertSessionHas('exito');

        Mail::assertQueued(EventoPublicado::class, 2);
        Mail::assertNotQueued(
            EventoPublicado::class,
            fn (EventoPublicado $correo) => $correo->hasTo('baja@ejemplo.com')
        );
    }

    #[Test]
    public function crear_un_evento_no_dispara_correos_masivos(): void
    {
        Mail::fake();

        Suscriptor::create(['email' => 'uno@ejemplo.com']);

        Evento::factory()->create();

        // La difusion es una accion explicita del panel, nunca automatica.
        Mail::assertNothingQueued();
    }

    #[Test]
    public function un_visitante_no_puede_disparar_la_difusion(): void
    {
        $evento = Evento::factory()->create();

        $this->post(route('admin.eventos.notificar', $evento))
            ->assertRedirect(route('login'));
    }
}
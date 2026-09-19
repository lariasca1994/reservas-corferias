<?php

namespace Tests\Feature;

use App\Mail\ReservaCancelada;
use App\Models\Escenario;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Registro publico y zona del cliente.
 */
class ClienteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function datosRegistro(array $sobrescribir = []): array
    {
        return array_merge([
            'name'                  => 'Ana Gómez',
            'email'                 => 'ana@ejemplo.com',
            'telefono'              => '3001234567',
            'password'              => 'clave-segura-2026',
            'password_confirmation' => 'clave-segura-2026',
        ], $sobrescribir);
    }

    #[Test]
    public function el_registro_crea_la_cuenta_con_rol_cliente(): void
    {
        $this->post(route('registro'), $this->datosRegistro())
            ->assertRedirect(route('verification.notice'));

        $usuario = User::where('email', 'ana@ejemplo.com')->firstOrFail();

        $this->assertSame(User::ROL_CLIENTE, $usuario->rol);
        $this->assertAuthenticatedAs($usuario);
    }

    #[Test]
    public function nadie_puede_registrarse_como_administrador(): void
    {
        // Aunque el rol viaje en la peticion, el controlador lo ignora.
        $this->post(route('registro'), $this->datosRegistro([
            'rol' => User::ROL_ADMINISTRADOR,
        ]));

        $this->assertSame(
            User::ROL_CLIENTE,
            User::where('email', 'ana@ejemplo.com')->firstOrFail()->rol
        );
    }

    #[Test]
    public function al_verificar_el_correo_se_asocian_las_reservas_previas_con_el_mismo_correo(): void
    {
        $escenario = Escenario::factory()->create();

        $previa = Reserva::factory()->for($escenario)->create([
            'email_contacto' => 'ana@ejemplo.com',
            'user_id'        => null,
        ]);

        $ajena = Reserva::factory()->for($escenario)->create([
            'email_contacto' => 'otro@ejemplo.com',
            'user_id'        => null,
        ]);

        $this->post(route('registro'), $this->datosRegistro());

        $usuario = User::where('email', 'ana@ejemplo.com')->firstOrFail();

        $url = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $usuario->id, 'hash' => sha1($usuario->email)]
        );

        $this->actingAs($usuario)->get($url);

        $this->assertSame($usuario->id, $previa->fresh()->user_id);
        $this->assertNull($ajena->fresh()->user_id);
    }

    #[Test]
    public function el_historial_solo_muestra_las_reservas_propias(): void
    {
        $escenario = Escenario::factory()->create();
        $ana       = User::factory()->create();
        $otro      = User::factory()->create();

        $suya  = Reserva::factory()->for($escenario)->create(['user_id' => $ana->id]);
        $ajena = Reserva::factory()->for($escenario)->create(['user_id' => $otro->id]);

        $this->actingAs($ana)
            ->get(route('mis-reservas.index'))
            ->assertOk()
            ->assertSee($suya->codigo)
            ->assertDontSee($ajena->codigo);
    }

    #[Test]
    public function un_visitante_sin_sesion_no_ve_el_historial(): void
    {
        $this->get(route('mis-reservas.index'))->assertRedirect(route('login'));
    }

    #[Test]
    public function el_cliente_puede_cancelar_su_propia_reserva(): void
    {
        Mail::fake();

        $ana     = User::factory()->create();
        $reserva = Reserva::factory()->for(Escenario::factory())->create(['user_id' => $ana->id]);

        $this->actingAs($ana)
            ->patch(route('mis-reservas.cancelar', $reserva), ['motivo' => 'Cambio de planes.'])
            ->assertSessionHas('exito');

        $this->assertSame(Reserva::ESTADO_CANCELADA, $reserva->fresh()->estado);
        Mail::assertQueued(ReservaCancelada::class);
    }

    #[Test]
    public function un_cliente_no_puede_cancelar_la_reserva_de_otro(): void
    {
        $ana     = User::factory()->create();
        $otro    = User::factory()->create();
        $reserva = Reserva::factory()->for(Escenario::factory())->create(['user_id' => $otro->id]);

        $this->actingAs($ana)
            ->patch(route('mis-reservas.cancelar', $reserva))
            ->assertForbidden();

        $this->assertNotSame(Reserva::ESTADO_CANCELADA, $reserva->fresh()->estado);
    }

    #[Test]
    public function la_reserva_creada_con_sesion_queda_asociada_a_la_cuenta(): void
    {
        Mail::fake();

        $ana       = User::factory()->create();
        $escenario = Escenario::factory()->create();

        $this->actingAs($ana)->post(route('reservas.store', $escenario), [
            'nombre_contacto'   => $ana->name,
            'email_contacto'    => $ana->email,
            'telefono_contacto' => '3001234567',
            'fecha_inicio'      => now()->addDays(15)->toDateString(),
            'fecha_fin'         => now()->addDays(17)->toDateString(),
        ]);

        $this->assertSame($ana->id, Reserva::firstOrFail()->user_id);
    }

    #[Test]
    public function un_cliente_es_llevado_a_su_historial_tras_iniciar_sesion(): void
    {
        User::factory()->create([
            'email'    => 'ana@ejemplo.com',
            'password' => bcrypt('clave-de-prueba'),
        ]);

        $this->post(route('login'), [
            'email'    => 'ana@ejemplo.com',
            'password' => 'clave-de-prueba',
        ])->assertRedirect(route('mis-reservas.index'));
    }
}
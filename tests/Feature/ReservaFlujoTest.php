<?php

namespace Tests\Feature;

use App\Models\Escenario;
use App\Models\Reserva;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Flujo de reserva a traves de HTTP.
 *
 * Estas pruebas verifican validacion, persistencia y redireccion. Las que
 * comprueban el contenido renderizado de las vistas llegan en la fase de
 * frontend, cuando las plantillas existan.
 */
class ReservaFlujoTest extends TestCase
{
    use RefreshDatabase;

    private Escenario $escenario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->escenario = Escenario::factory()->create(['slug' => 'alfa']);
    }

    /**
     * @return array<string, mixed>
     */
    private function datosValidos(array $sobrescribir = []): array
    {
        return array_merge([
            'nombre_contacto'   => 'Ana Gomez',
            'email_contacto'    => 'ana@ejemplo.com',
            'telefono_contacto' => '3001234567',
            'fecha_inicio'      => now()->addDays(20)->toDateString(),
            'fecha_fin'         => now()->addDays(22)->toDateString(),
        ], $sobrescribir);
    }

    #[Test]
    public function registra_la_reserva_y_redirige_al_comprobante(): void
    {
        $respuesta = $this->post(
            route('reservas.store', $this->escenario),
            $this->datosValidos()
        );

        $reserva = Reserva::firstOrFail();

        $respuesta->assertRedirect(route('reservas.show', $reserva));
        $respuesta->assertSessionHas('exito');

        $this->assertDatabaseCount('reservas', 1);
        $this->assertSame(Reserva::ESTADO_PENDIENTE, $reserva->estado);
    }

    #[Test]
    public function rechaza_fechas_en_el_pasado(): void
    {
        $respuesta = $this->post(
            route('reservas.store', $this->escenario),
            $this->datosValidos(['fecha_inicio' => now()->subDay()->toDateString()])
        );

        $respuesta->assertSessionHasErrors('fecha_inicio');
        $this->assertDatabaseCount('reservas', 0);
    }

    #[Test]
    public function rechaza_una_fecha_final_anterior_a_la_inicial(): void
    {
        $respuesta = $this->post(
            route('reservas.store', $this->escenario),
            $this->datosValidos([
                'fecha_inicio' => now()->addDays(20)->toDateString(),
                'fecha_fin'    => now()->addDays(18)->toDateString(),
            ])
        );

        $respuesta->assertSessionHasErrors('fecha_fin');
        $this->assertDatabaseCount('reservas', 0);
    }

    #[Test]
    public function rechaza_un_rango_que_supera_el_maximo_permitido(): void
    {
        $respuesta = $this->post(
            route('reservas.store', $this->escenario),
            $this->datosValidos([
                'fecha_inicio' => now()->addDays(10)->toDateString(),
                'fecha_fin'    => now()->addDays(50)->toDateString(),
            ])
        );

        $respuesta->assertSessionHasErrors('fecha_fin');
        $this->assertDatabaseCount('reservas', 0);
    }

    #[Test]
    public function rechaza_un_correo_con_formato_invalido(): void
    {
        $respuesta = $this->post(
            route('reservas.store', $this->escenario),
            $this->datosValidos(['email_contacto' => 'esto-no-es-un-correo'])
        );

        $respuesta->assertSessionHasErrors('email_contacto');
    }

    #[Test]
    public function impide_reservar_fechas_que_se_cruzan_con_otra_reserva(): void
    {
        Reserva::factory()
            ->for($this->escenario)
            ->entre(now()->addDays(20)->toDateString(), now()->addDays(25)->toDateString())
            ->create();

        // Cruce parcial: justo el caso que el sistema de 2019 dejaba pasar.
        $respuesta = $this->post(
            route('reservas.store', $this->escenario),
            $this->datosValidos([
                'fecha_inicio' => now()->addDays(23)->toDateString(),
                'fecha_fin'    => now()->addDays(28)->toDateString(),
            ])
        );

        $respuesta->assertSessionHasErrors('fecha_inicio');
        $this->assertDatabaseCount('reservas', 1);
    }

    #[Test]
    public function permite_reservar_el_dia_siguiente_al_fin_de_otra_reserva(): void
    {
        Reserva::factory()
            ->for($this->escenario)
            ->entre(now()->addDays(20)->toDateString(), now()->addDays(25)->toDateString())
            ->create();

        $this->post(
            route('reservas.store', $this->escenario),
            $this->datosValidos([
                'fecha_inicio' => now()->addDays(26)->toDateString(),
                'fecha_fin'    => now()->addDays(28)->toDateString(),
            ])
        )->assertSessionHasNoErrors();

        $this->assertDatabaseCount('reservas', 2);
    }

    #[Test]
    public function no_permite_reservar_un_escenario_inactivo(): void
    {
        $inactivo = Escenario::factory()->inactivo()->create();

        $this->post(route('reservas.store', $inactivo), $this->datosValidos())
            ->assertNotFound();
    }

    #[Test]
    public function el_endpoint_de_disponibilidad_devuelve_los_rangos_ocupados(): void
    {
        Reserva::factory()
            ->for($this->escenario)
            ->entre(now()->addDays(20)->toDateString(), now()->addDays(25)->toDateString())
            ->create();

        $this->getJson(route('escenarios.disponibilidad', $this->escenario))
            ->assertOk()
            ->assertJsonPath('escenario', 'alfa')
            ->assertJsonCount(1, 'ocupados')
            ->assertJsonPath('ocupados.0.inicio', now()->addDays(20)->toDateString());
    }

    #[Test]
    public function el_comprobante_no_existe_para_un_codigo_desconocido(): void
    {
        $this->get(route('reservas.show', 'RSV-NOEXISTE'))->assertNotFound();
    }
}

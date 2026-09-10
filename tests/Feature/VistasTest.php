<?php

namespace Tests\Feature;

use App\Models\Escenario;
use App\Models\Evento;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Renderizado de las vistas publicas y del panel.
 *
 * Complementan las pruebas de las fases anteriores, que se detenian en la
 * respuesta HTTP sin comprobar el contenido porque las plantillas todavia
 * no existian.
 */
class VistasTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function el_inicio_muestra_escenarios_y_eventos_destacados(): void
    {
        $escenario = Escenario::factory()->create(['nombre' => 'Escenario Alfa']);
        Evento::factory()->destacado()->create(['nombre' => 'Festival de Comics']);

        $this->get(route('inicio'))
            ->assertOk()
            ->assertSee('Escenario Alfa')
            ->assertSee('Festival de Comics')
            ->assertSee($escenario->precioFormateado());
    }

    #[Test]
    public function el_detalle_del_escenario_muestra_sus_fechas_ocupadas(): void
    {
        $escenario = Escenario::factory()->create(['slug' => 'alfa']);

        Reserva::factory()->for($escenario)
            ->entre(now()->addDays(10)->toDateString(), now()->addDays(12)->toDateString())
            ->create();

        $this->get(route('escenarios.show', $escenario))
            ->assertOk()
            ->assertSee($escenario->nombre)
            ->assertSee(now()->addDays(10)->format('d/m/Y'));
    }

    #[Test]
    public function cada_escenario_muestra_su_propio_contenido(): void
    {
        $alfa  = Escenario::factory()->create(['slug' => 'alfa', 'nombre' => 'Escenario Alfa']);
        $omega = Escenario::factory()->create(['slug' => 'omega', 'nombre' => 'Escenario Omega']);

        $this->get(route('escenarios.show', $alfa))
            ->assertSee('Escenario Alfa')
            ->assertDontSee('Escenario Omega');

        $this->get(route('escenarios.show', $omega))
            ->assertSee('Escenario Omega')
            ->assertDontSee('Escenario Alfa');
    }

    #[Test]
    public function el_formulario_de_reserva_se_renderiza(): void
    {
        $escenario = Escenario::factory()->create();

        $this->get(route('reservas.create', $escenario))
            ->assertOk()
            ->assertSee('Solicitud de reserva')
            ->assertSee('fecha_inicio', false)
            ->assertSee('_token', false);
    }

    #[Test]
    public function el_comprobante_muestra_el_codigo_y_el_qr(): void
    {
        $reserva = Reserva::factory()->for(Escenario::factory())->create();

        $this->get(route('reservas.show', $reserva))
            ->assertOk()
            ->assertSee($reserva->codigo)
            ->assertSee('<svg', false);
    }

    #[Test]
    public function el_comprobante_de_una_reserva_cancelada_no_muestra_qr(): void
    {
        $reserva = Reserva::factory()->for(Escenario::factory())->cancelada()->create();

        $this->get(route('reservas.show', $reserva))
            ->assertOk()
            ->assertSee('Cancelada')
            ->assertDontSee('<svg', false);
    }

    #[Test]
    public function el_detalle_del_evento_muestra_su_descripcion(): void
    {
        $evento = Evento::factory()->create([
            'nombre'      => 'Feria del Libro',
            'descripcion' => 'Editoriales y presentaciones de autor.',
        ]);

        $this->get(route('eventos.show', $evento))
            ->assertOk()
            ->assertSee('Feria del Libro')
            ->assertSee('Editoriales y presentaciones de autor.');
    }

    #[Test]
    public function el_panel_muestra_el_conteo_de_reservas_pendientes(): void
    {
        $escenario = Escenario::factory()->create();
        Reserva::factory()->for($escenario)->pendiente()->count(3)->create();

        $this->actingAs(User::factory()->operador()->create())
            ->get(route('admin.panel'))
            ->assertOk()
            ->assertSee('Pendientes');
    }

    #[Test]
    public function el_menu_del_panel_oculta_usuarios_a_los_operadores(): void
    {
        $this->actingAs(User::factory()->operador()->create())
            ->get(route('admin.panel'))
            ->assertOk()
            ->assertDontSee(route('admin.usuarios.index'));
    }

    #[Test]
    public function el_menu_del_panel_muestra_usuarios_a_los_administradores(): void
    {
        $this->actingAs(User::factory()->administrador()->create())
            ->get(route('admin.panel'))
            ->assertOk()
            ->assertSee(route('admin.usuarios.index'));
    }
}

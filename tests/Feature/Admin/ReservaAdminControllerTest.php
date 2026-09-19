<?php

namespace Tests\Feature\Admin;

use App\Models\Escenario;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReservaAdminControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $gestor;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->gestor = User::factory()->administrador()->create();
    }

    #[Test]
    public function el_listado_muestra_las_reservas(): void
    {
        $reserva = Reserva::factory()->for(Escenario::factory())->create();

        $this->actingAs($this->gestor)
            ->get(route('admin.reservas.index'))
            ->assertOk()
            ->assertSee($reserva->codigo);
    }

    #[Test]
    public function el_listado_se_puede_filtrar_por_estado(): void
    {
        $escenario  = Escenario::factory()->create();
        $pendiente  = Reserva::factory()->for($escenario)->create(['estado' => Reserva::ESTADO_PENDIENTE]);
        $confirmada = Reserva::factory()->for($escenario)->create(['estado' => Reserva::ESTADO_CONFIRMADA]);

        $this->actingAs($this->gestor)
            ->get(route('admin.reservas.index', ['estado' => Reserva::ESTADO_CONFIRMADA]))
            ->assertOk()
            ->assertSee($confirmada->codigo)
            ->assertDontSee($pendiente->codigo);
    }

    #[Test]
    public function el_listado_se_puede_buscar_por_codigo(): void
    {
        $escenario = Escenario::factory()->create();
        $buscada   = Reserva::factory()->for($escenario)->create();
        $otra      = Reserva::factory()->for($escenario)->create();

        $this->actingAs($this->gestor)
            ->get(route('admin.reservas.index', ['buscar' => $buscada->codigo]))
            ->assertOk()
            ->assertSee($buscada->codigo)
            ->assertDontSee($otra->codigo);
    }

    #[Test]
    public function el_detalle_muestra_la_reserva(): void
    {
        $reserva = Reserva::factory()->for(Escenario::factory())->create();

        $this->actingAs($this->gestor)
            ->get(route('admin.reservas.show', $reserva))
            ->assertOk()
            ->assertSee($reserva->codigo);
    }

    #[Test]
    public function confirmar_una_reserva_pendiente_la_deja_confirmada(): void
    {
        $reserva = Reserva::factory()->for(Escenario::factory())->create([
            'estado' => Reserva::ESTADO_PENDIENTE,
        ]);

        $this->actingAs($this->gestor)
            ->patch(route('admin.reservas.confirmar', $reserva))
            ->assertSessionHas('exito');

        $this->assertSame(Reserva::ESTADO_CONFIRMADA, $reserva->fresh()->estado);
    }

    #[Test]
    public function no_se_puede_confirmar_una_reserva_ya_cancelada(): void
    {
        $reserva = Reserva::factory()->for(Escenario::factory())->create([
            'estado' => Reserva::ESTADO_CANCELADA,
        ]);

        $this->actingAs($this->gestor)
            ->patch(route('admin.reservas.confirmar', $reserva))
            ->assertSessionHas('aviso');

        $this->assertSame(Reserva::ESTADO_CANCELADA, $reserva->fresh()->estado);
    }

    #[Test]
    public function cancelar_una_reserva_registra_el_motivo(): void
    {
        $reserva = Reserva::factory()->for(Escenario::factory())->create([
            'estado' => Reserva::ESTADO_PENDIENTE,
        ]);

        $this->actingAs($this->gestor)
            ->patch(route('admin.reservas.cancelar', $reserva), ['motivo' => 'Escenario en mantenimiento.'])
            ->assertSessionHas('exito');

        $this->assertSame(Reserva::ESTADO_CANCELADA, $reserva->fresh()->estado);
    }

    #[Test]
    public function un_operador_tambien_puede_gestionar_reservas(): void
    {
        $operador = User::factory()->operador()->create();
        $reserva  = Reserva::factory()->for(Escenario::factory())->create([
            'estado' => Reserva::ESTADO_PENDIENTE,
        ]);

        $this->actingAs($operador)
            ->patch(route('admin.reservas.confirmar', $reserva))
            ->assertSessionHas('exito');
    }
}

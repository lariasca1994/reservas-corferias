<?php

namespace Tests\Feature\Admin;

use App\Models\Escenario;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EscenarioCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $gestor;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->gestor = User::factory()->administrador()->create();
    }

    /**
     * @return array<string, mixed>
     */
    private function datos(array $sobrescribir = []): array
    {
        return array_merge([
            'nombre'          => 'Escenario Delta',
            'slug'            => 'delta',
            'resumen'         => 'Espacio modular para ferias de mediano formato.',
            'descripcion'     => 'Descripción extendida del escenario.',
            'precio_dia'      => 12000000,
            'capacidad'       => 900,
            'activo'          => true,
            'imagen'          => UploadedFile::fake()->image('delta.jpg', 1200, 800),
            'caracteristicas' => [
                ['titulo' => 'Modular', 'descripcion' => 'Se divide en tres salas independientes.'],
                ['titulo' => 'Parqueadero', 'descripcion' => 'Cupo para 120 vehículos.'],
            ],
        ], $sobrescribir);
    }

    #[Test]
    public function crea_un_escenario_con_su_imagen_y_caracteristicas(): void
    {
        $this->actingAs($this->gestor)
            ->post(route('admin.escenarios.store'), $this->datos())
            ->assertRedirect(route('admin.escenarios.index'));

        $escenario = Escenario::where('slug', 'delta')->firstOrFail();

        $this->assertCount(2, $escenario->caracteristicas);
        Storage::disk('public')->assertExists($escenario->imagen_principal);
        $this->assertStringStartsWith('escenarios/', $escenario->imagen_principal);
    }

    #[Test]
    public function el_nombre_del_archivo_subido_no_se_conserva(): void
    {
        $this->actingAs($this->gestor)->post(
            route('admin.escenarios.store'),
            $this->datos(['imagen' => UploadedFile::fake()->image('../../etc/passwd.jpg', 1200, 800)])
        );

        $escenario = Escenario::where('slug', 'delta')->firstOrFail();

        $this->assertStringNotContainsString('passwd', $escenario->imagen_principal);
        $this->assertStringNotContainsString('..', $escenario->imagen_principal);
    }

    #[Test]
    public function rechaza_un_archivo_que_no_es_imagen(): void
    {
        $this->actingAs($this->gestor)
            ->post(route('admin.escenarios.store'), $this->datos([
                'imagen' => UploadedFile::fake()->create('documento.pdf', 500, 'application/pdf'),
            ]))
            ->assertSessionHasErrors('imagen');

        $this->assertDatabaseCount('escenarios', 0);
    }

    #[Test]
    public function rechaza_una_imagen_demasiado_pequena(): void
    {
        $this->actingAs($this->gestor)
            ->post(route('admin.escenarios.store'), $this->datos([
                'imagen' => UploadedFile::fake()->image('mini.jpg', 100, 80),
            ]))
            ->assertSessionHasErrors('imagen');
    }

    #[Test]
    public function rechaza_un_slug_repetido(): void
    {
        Escenario::factory()->create(['slug' => 'delta']);

        $this->actingAs($this->gestor)
            ->post(route('admin.escenarios.store'), $this->datos())
            ->assertSessionHasErrors('slug');
    }

    #[Test]
    public function al_actualizar_la_imagen_se_borra_la_anterior(): void
    {
        $this->actingAs($this->gestor)->post(route('admin.escenarios.store'), $this->datos());

        $escenario = Escenario::where('slug', 'delta')->firstOrFail();
        $anterior  = $escenario->imagen_principal;

        $this->actingAs($this->gestor)->put(
            route('admin.escenarios.update', $escenario),
            $this->datos(['imagen' => UploadedFile::fake()->image('nueva.jpg', 1200, 800)])
        );

        $nueva = $escenario->fresh()->imagen_principal;

        $this->assertNotSame($anterior, $nueva);
        Storage::disk('public')->assertMissing($anterior);
        Storage::disk('public')->assertExists($nueva);
    }

    #[Test]
    public function un_escenario_con_reservas_se_desactiva_en_lugar_de_borrarse(): void
    {
        $escenario = Escenario::factory()->create();
        Reserva::factory()->for($escenario)->create();

        $this->actingAs($this->gestor)
            ->delete(route('admin.escenarios.destroy', $escenario))
            ->assertSessionHas('aviso');

        $this->assertDatabaseHas('escenarios', [
            'id'     => $escenario->id,
            'activo' => false,
        ]);
    }

    #[Test]
    public function un_escenario_sin_reservas_si_se_elimina(): void
    {
        $escenario = Escenario::factory()->create();

        $this->actingAs($this->gestor)
            ->delete(route('admin.escenarios.destroy', $escenario));

        $this->assertDatabaseMissing('escenarios', ['id' => $escenario->id]);
    }
}

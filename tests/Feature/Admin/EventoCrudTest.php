<?php

namespace Tests\Feature\Admin;

use App\Models\Escenario;
use App\Models\Evento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EventoCrudTest extends TestCase
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
            'nombre'       => 'Feria del Libro',
            'slug'         => 'feria-del-libro',
            'resumen'      => 'Encuentro anual de editoriales y lectores.',
            'descripcion'  => 'Descripción extendida del evento.',
            'fecha_inicio' => now()->addDays(30)->toDateString(),
            'fecha_fin'    => now()->addDays(35)->toDateString(),
            'horario'      => '9:00 a. m. - 6:00 p. m.',
            'destacado'    => true,
            'imagen'       => UploadedFile::fake()->image('feria.jpg', 1200, 800),
        ], $sobrescribir);
    }

    #[Test]
    public function crea_un_evento_con_su_imagen(): void
    {
        $this->actingAs($this->gestor)
            ->post(route('admin.eventos.store'), $this->datos())
            ->assertRedirect(route('admin.eventos.index'));

        $evento = Evento::where('slug', 'feria-del-libro')->firstOrFail();

        Storage::disk('public')->assertExists($evento->imagen);
        $this->assertStringStartsWith('eventos/', $evento->imagen);
        $this->assertTrue($evento->destacado);
    }

    #[Test]
    public function rechaza_una_fecha_final_anterior_a_la_inicial(): void
    {
        $this->actingAs($this->gestor)
            ->post(route('admin.eventos.store'), $this->datos([
                'fecha_inicio' => now()->addDays(10)->toDateString(),
                'fecha_fin'    => now()->addDays(5)->toDateString(),
            ]))
            ->assertSessionHasErrors('fecha_fin');

        $this->assertDatabaseCount('eventos', 0);
    }

    #[Test]
    public function rechaza_un_slug_repetido(): void
    {
        Evento::factory()->create(['slug' => 'feria-del-libro']);

        $this->actingAs($this->gestor)
            ->post(route('admin.eventos.store'), $this->datos())
            ->assertSessionHasErrors('slug');
    }

    #[Test]
    public function exige_imagen_al_crear(): void
    {
        $this->actingAs($this->gestor)
            ->post(route('admin.eventos.store'), $this->datos(['imagen' => null]))
            ->assertSessionHasErrors('imagen');
    }

    #[Test]
    public function puede_asociarse_a_un_escenario(): void
    {
        $escenario = Escenario::factory()->create();

        $this->actingAs($this->gestor)
            ->post(route('admin.eventos.store'), $this->datos(['escenario_id' => $escenario->id]));

        $evento = Evento::where('slug', 'feria-del-libro')->firstOrFail();

        $this->assertSame($escenario->id, $evento->escenario_id);
    }

    #[Test]
    public function al_actualizar_sin_imagen_nueva_se_conserva_la_anterior(): void
    {
        $this->actingAs($this->gestor)->post(route('admin.eventos.store'), $this->datos());

        $evento   = Evento::where('slug', 'feria-del-libro')->firstOrFail();
        $anterior = $evento->imagen;

        $this->actingAs($this->gestor)->put(
            route('admin.eventos.update', $evento),
            $this->datos(['imagen' => null, 'nombre' => 'Feria del Libro (actualizada)'])
        );

        $evento->refresh();

        $this->assertSame($anterior, $evento->imagen);
        $this->assertSame('Feria del Libro (actualizada)', $evento->nombre);
    }

    #[Test]
    public function al_actualizar_la_imagen_se_borra_la_anterior(): void
    {
        $this->actingAs($this->gestor)->post(route('admin.eventos.store'), $this->datos());

        $evento   = Evento::where('slug', 'feria-del-libro')->firstOrFail();
        $anterior = $evento->imagen;

        $this->actingAs($this->gestor)->put(
            route('admin.eventos.update', $evento),
            $this->datos(['imagen' => UploadedFile::fake()->image('nueva.jpg', 1200, 800)])
        );

        $nueva = $evento->fresh()->imagen;

        $this->assertNotSame($anterior, $nueva);
        Storage::disk('public')->assertMissing($anterior);
        Storage::disk('public')->assertExists($nueva);
    }

    #[Test]
    public function eliminar_un_evento_borra_su_imagen(): void
    {
        $this->actingAs($this->gestor)->post(route('admin.eventos.store'), $this->datos());

        $evento = Evento::where('slug', 'feria-del-libro')->firstOrFail();
        $imagen = $evento->imagen;

        $this->actingAs($this->gestor)
            ->delete(route('admin.eventos.destroy', $evento))
            ->assertRedirect(route('admin.eventos.index'));

        $this->assertDatabaseMissing('eventos', ['id' => $evento->id]);
        Storage::disk('public')->assertMissing($imagen);
    }
}

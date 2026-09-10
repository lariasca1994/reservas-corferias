<?php

namespace Tests\Feature;

use App\Models\Escenario;
use App\Models\Evento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Resolucion de rutas con parametro.
 *
 * En la version de 2019 las rutas /scenarios/{type} y /events/{id}
 * aceptaban cualquier valor y devolvian siempre el mismo contenido: el
 * parametro llegaba al controlador y se descartaba. Estas pruebas fijan
 * el comportamiento correcto para que no pueda volver a ocurrir.
 */
class RutasConParametroTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function un_slug_de_escenario_inexistente_responde_404(): void
    {
        Escenario::factory()->create(['slug' => 'alfa']);

        $this->get('/escenarios/no-existe')->assertNotFound();
    }

    #[Test]
    public function un_escenario_inactivo_no_es_visible(): void
    {
        $inactivo = Escenario::factory()->inactivo()->create(['slug' => 'retirado']);

        $this->get(route('escenarios.show', $inactivo))->assertNotFound();
    }

    #[Test]
    public function el_endpoint_de_disponibilidad_distingue_entre_escenarios(): void
    {
        $alfa  = Escenario::factory()->create(['slug' => 'alfa']);
        $omega = Escenario::factory()->create(['slug' => 'omega']);

        $this->getJson(route('escenarios.disponibilidad', $alfa))
            ->assertJsonPath('escenario', 'alfa');

        $this->getJson(route('escenarios.disponibilidad', $omega))
            ->assertJsonPath('escenario', 'omega');
    }

    #[Test]
    public function un_slug_de_evento_inexistente_responde_404(): void
    {
        Evento::factory()->create(['slug' => 'feria-del-libro']);

        $this->get('/eventos/evento-fantasma')->assertNotFound();
    }
}

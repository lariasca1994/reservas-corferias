<?php

namespace Tests\Feature;

use App\Mail\ReservaCancelada;
use App\Mail\ReservaConfirmada;
use App\Mail\ReservaRegistrada;
use App\Models\Escenario;
use App\Models\Reserva;
use App\Services\CodigoQrService;
use App\Services\EstadoReservaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificacionReservaTest extends TestCase
{
    use RefreshDatabase;

    private Escenario $escenario;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->escenario = Escenario::factory()->create(['slug' => 'alfa']);
    }

    #[Test]
    public function al_registrar_una_reserva_se_envia_el_correo_de_solicitud(): void
    {
        $this->post(route('reservas.store', $this->escenario), [
            'nombre_contacto'   => 'Ana Gomez',
            'email_contacto'    => 'ana@ejemplo.com',
            'telefono_contacto' => '3001234567',
            'fecha_inicio'      => now()->addDays(20)->toDateString(),
            'fecha_fin'         => now()->addDays(22)->toDateString(),
        ]);

        Mail::assertQueued(
            ReservaRegistrada::class,
            fn (ReservaRegistrada $correo) => $correo->hasTo('ana@ejemplo.com')
        );
    }

    #[Test]
    public function confirmar_una_reserva_envia_el_correo_de_confirmacion(): void
    {
        $reserva = Reserva::factory()->for($this->escenario)->pendiente()->create();

        app(EstadoReservaService::class)->confirmar($reserva);

        Mail::assertQueued(ReservaConfirmada::class);
        $this->assertSame(Reserva::ESTADO_CONFIRMADA, $reserva->fresh()->estado);
    }

    #[Test]
    public function cancelar_una_reserva_envia_el_correo_de_cancelacion(): void
    {
        $reserva = Reserva::factory()->for($this->escenario)->create();

        app(EstadoReservaService::class)->cancelar($reserva, 'El cliente desistió.');

        Mail::assertQueued(ReservaCancelada::class);
        $this->assertSame('El cliente desistió.', $reserva->fresh()->observaciones);
    }

    #[Test]
    public function editar_un_dato_que_no_es_el_estado_no_genera_correos(): void
    {
        $reserva = Reserva::factory()->for($this->escenario)->create();

        Mail::fake(); // descarta el correo de creacion

        $reserva->update(['telefono_contacto' => '3009999999']);

        Mail::assertNothingQueued();
    }

    #[Test]
    public function una_reserva_cancelada_no_puede_volver_a_confirmarse(): void
    {
        $reserva = Reserva::factory()->for($this->escenario)->cancelada()->create();

        $this->expectException(InvalidArgumentException::class);

        app(EstadoReservaService::class)->confirmar($reserva);
    }

    #[Test]
    public function el_qr_codifica_la_url_publica_del_comprobante(): void
    {
        $reserva = Reserva::factory()->for($this->escenario)->create();

        $servicio = app(CodigoQrService::class);

        $this->assertSame(
            route('reservas.show', $reserva, absolute: true),
            $servicio->contenidoPara($reserva)
        );
    }

    #[Test]
    public function el_qr_se_genera_en_formato_svg(): void
    {
        $reserva = Reserva::factory()->for($this->escenario)->create();

        $svg = app(CodigoQrService::class)->svg($reserva);

        $this->assertStringContainsString('<svg', $svg);
        $this->assertStringContainsString('</svg>', $svg);
    }

    #[Test]
    public function el_qr_en_png_devuelve_null_sin_imagick_en_lugar_de_fallar(): void
    {
        $reserva = Reserva::factory()->for($this->escenario)->create();

        $png = app(CodigoQrService::class)->png($reserva);

        // Con imagick disponible devuelve binario PNG; sin ella, null.
        // Lo que se verifica es que nunca lance excepcion.
        $this->assertTrue($png === null || str_starts_with($png, "\x89PNG"));
    }
}

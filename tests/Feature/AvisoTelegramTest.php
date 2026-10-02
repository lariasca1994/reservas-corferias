<?php

namespace Tests\Feature;

use App\Models\Escenario;
use App\Models\Reserva;
use App\Services\AvisoTelegram;
use App\Services\CodigoQrService;
use Carbon\Carbon;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AvisoTelegramTest extends TestCase
{
    private function reserva(): Reserva
    {
        $reserva = new Reserva;
        $reserva->forceFill([
            'codigo'          => 'ABC123',
            'nombre_contacto' => 'Ana <b>Pérez</b>',
            'email_contacto'  => 'ana@ejemplo.com',
            'fecha_inicio'    => Carbon::parse('2026-10-14'),
            'fecha_fin'       => Carbon::parse('2026-10-16'),
            'estado'          => Reserva::ESTADO_CONFIRMADA,
            'observaciones'   => 'Montaje tipo auditorio',
        ]);
        $reserva->setRelation('escenario', new Escenario(['nombre' => 'Gran Salón', 'precio_dia' => 1000]));

        return $reserva;
    }

    #[Test]
    public function avisa_la_reserva_con_sus_datos_notas_y_boton_al_comprobante(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        config(['services.telegram.token' => 'token-de-prueba', 'services.telegram.chat_id' => '123']);

        app(AvisoTelegram::class)->reserva($this->reserva(), app(CodigoQrService::class));

        Http::assertSent(fn (Request $r) => $r->url() === 'https://api.telegram.org/bottoken-de-prueba/sendMessage'
            && $r['chat_id'] === '123'
            && $r['parse_mode'] === 'HTML'
            && str_contains($r['text'], 'Reserva confirmada')
            && str_contains($r['text'], 'ABC123')
            && str_contains($r['text'], 'Ana &lt;b&gt;Pérez&lt;/b&gt;')
            && str_contains($r['text'], 'Montaje tipo auditorio')
            && $r['reply_markup']['inline_keyboard'][0][0]['text'] === 'Ver comprobante');
    }

    #[Test]
    public function sin_configuracion_no_envia_nada(): void
    {
        Http::fake();

        app(AvisoTelegram::class)->reserva($this->reserva(), app(CodigoQrService::class));

        Http::assertNothingSent();
    }

    #[Test]
    public function un_fallo_de_telegram_no_interrumpe_la_operacion(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => false], 400)]);
        config(['services.telegram.token' => 'token-de-prueba', 'services.telegram.chat_id' => '123']);

        app(AvisoTelegram::class)->reserva($this->reserva(), app(CodigoQrService::class));

        $this->assertTrue(true);
    }
}

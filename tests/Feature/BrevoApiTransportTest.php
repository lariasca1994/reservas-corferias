<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class BrevoApiTransportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'mail.default'           => 'brevo',
            'mail.mailers.brevo.key' => 'clave-de-prueba',
            'mail.from.address'      => 'reservas@ejemplo.com',
            'mail.from.name'         => 'Reservas',
        ]);
    }

    #[Test]
    public function envia_el_correo_por_la_api_con_la_clave_y_sin_credenciales_smtp(): void
    {
        Http::fake(['api.brevo.com/*' => Http::response(['messageId' => '<abc@brevo>'], 201)]);

        Mail::html('<p>Hola</p>', function ($m) {
            $m->to('ana@ejemplo.com', 'Ana')->cc('copia@ejemplo.com')->subject('Prueba');
        });

        Http::assertSent(function (Request $r) {
            return $r->url() === 'https://api.brevo.com/v3/smtp/email'
                && $r->header('api-key') === ['clave-de-prueba']
                && $r['sender'] === ['email' => 'reservas@ejemplo.com', 'name' => 'Reservas']
                && $r['to'] === [['email' => 'ana@ejemplo.com', 'name' => 'Ana']]
                && $r['cc'] === [['email' => 'copia@ejemplo.com']]
                && $r['subject'] === 'Prueba'
                && $r['htmlContent'] === '<p>Hola</p>';
        });
    }

    #[Test]
    public function agrega_la_copia_oculta_configurada_sin_duplicar_destinatarios(): void
    {
        Http::fake(['api.brevo.com/*' => Http::response([], 201)]);
        config(['mail.copia_oculta' => ['control@ejemplo.com', 'ANA@ejemplo.com']]);

        Mail::html('<p>Hola</p>', fn ($m) => $m->to('ana@ejemplo.com')->subject('Copia'));

        Http::assertSent(fn (Request $r) => $r['to'] === [['email' => 'ana@ejemplo.com']]
            && $r['bcc'] === [['email' => 'control@ejemplo.com']]);
    }

    #[Test]
    public function las_imagenes_en_linea_se_referencian_por_nombre_de_archivo(): void
    {
        Http::fake(['api.brevo.com/*' => Http::response([], 201)]);

        Mail::send([], [], function ($m) {
            $cid = $m->embedData('png-falso', 'reserva-ABC.png', 'image/png');
            $m->to('ana@ejemplo.com')->subject('QR')->html('<img src="'.$cid.'">');
        });

        Http::assertSent(fn (Request $r) => $r['htmlContent'] === '<img src="cid:reserva-ABC.png">'
            && $r['attachment'] === [['name' => 'reserva-ABC.png', 'content' => base64_encode('png-falso')]]);
    }

    #[Test]
    public function un_rechazo_de_brevo_lanza_excepcion_para_que_la_cola_reintente(): void
    {
        Http::fake(['api.brevo.com/*' => Http::response(['message' => 'Key not found'], 401)]);

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Key not found');

        Mail::raw('Hola', fn ($m) => $m->to('ana@ejemplo.com')->subject('Prueba'));
    }
}

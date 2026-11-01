<?php

namespace Tests\Feature;

use App\Services\AlmacenamientoImagenService;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ImagenesCloudinaryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.cloudinary.url' => 'cloudinary://clave-prueba:secreto-prueba@nube-prueba']);
    }

    #[Test]
    public function con_cloudinary_la_imagen_se_sube_y_se_guarda_su_identificador(): void
    {
        Http::fake(fn (Request $r) => Http::response(['public_id' => $this->campo($r, 'public_id')]));

        $ruta = app(AlmacenamientoImagenService::class)->guardar(UploadedFile::fake()->image('foto.jpg', 600, 400), 'escenarios');

        $this->assertMatchesRegularExpression('#^cloudinary:reservas-corferias/escenarios/[0-9a-f-]{36}$#', $ruta);
        Http::assertSent(fn (Request $r) => str_contains($r->url(), '/nube-prueba/image/upload')
            && $this->campo($r, 'api_key') === 'clave-prueba' && strlen((string) $this->campo($r, 'signature')) === 40);
    }

    /** Campo de una peticion multipart (las partes llegan como lista de name/contents). */
    private function campo(Request $r, string $nombre): mixed
    {
        foreach ($r->data() as $parte) {
            if (is_array($parte) && ($parte['name'] ?? null) === $nombre) {
                return $parte['contents'];
            }
        }

        return null;
    }

    #[Test]
    public function la_url_publica_sale_de_cloudinary(): void
    {
        $url = app(AlmacenamientoImagenService::class)->url('cloudinary:reservas-corferias/escenarios/abc');

        $this->assertSame('https://res.cloudinary.com/nube-prueba/image/upload/f_auto,q_auto/reservas-corferias/escenarios/abc', $url);
    }

    #[Test]
    public function borrar_una_imagen_la_quita_de_cloudinary(): void
    {
        Http::fake(['*' => Http::response(['result' => 'ok'])]);

        app(AlmacenamientoImagenService::class)->eliminar('cloudinary:reservas-corferias/escenarios/abc');

        Http::assertSent(fn (Request $r) => str_contains($r->url(), '/image/destroy')
            && $r['public_id'] === 'reservas-corferias/escenarios/abc');
    }

    #[Test]
    public function si_cloudinary_falla_no_se_guarda_una_ruta_invalida(): void
    {
        Http::fake(['*' => Http::response(['error' => ['message' => 'x']], 500)]);

        $this->expectException(\RuntimeException::class);
        app(AlmacenamientoImagenService::class)->guardar(UploadedFile::fake()->image('foto.jpg'), 'escenarios');
    }
}

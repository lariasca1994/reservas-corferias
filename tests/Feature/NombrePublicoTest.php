<?php

namespace Tests\Feature;

use App\Mail\EventoPublicado;
use App\Mail\ReservaRegistrada;
use App\Models\Escenario;
use App\Models\Evento;
use App\Models\Reserva;
use App\Models\Suscriptor;
use App\Models\User;
use App\Notifications\CodigoRecuperacion;
use App\Notifications\CodigoVerificacion;
use App\Notifications\ConfirmarCorreo;
use App\Notifications\RestablecerContrasena;
use App\Providers\NombrePublico;
use App\Services\DosPasos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * El nombre descriptivo del portal aparece en todos los correos (asunto,
 * encabezado y pie), en el remitente y en el emisor del QR de la app
 * autenticadora. El nombre anterior no debe verse.
 */
class NombrePublicoTest extends TestCase
{
    use RefreshDatabase;

    private const NOMBRE = 'Reservas de recinto ferial';

    public static function valoresDeLaNube(): array
    {
        return [
            'nombre anterior' => ['Reservas Corferias'],
            'vacio'           => [''],
            'por defecto'     => ['Laravel'],
        ];
    }

    #[Test]
    #[DataProvider('valoresDeLaNube')]
    public function el_remitente_y_el_nombre_usan_el_descriptivo_si_la_nube_trae_otro(string $valor): void
    {
        config(['app.name' => $valor, 'mail.from.name' => $valor]);

        NombrePublico::aplicar();

        $this->assertSame(self::NOMBRE, config('app.name'));
        $this->assertSame(self::NOMBRE, config('mail.from.name'));
    }

    #[Test]
    public function brevo_recibe_el_remitente_con_el_nombre_descriptivo(): void
    {
        config([
            'mail.default'           => 'brevo',
            'mail.mailers.brevo.key' => 'clave-de-prueba',
            'mail.from.address'      => 'reservas@ejemplo.com',
            'mail.from.name'         => 'Reservas Corferias',
        ]);
        NombrePublico::aplicar();
        Http::fake(['api.brevo.com/*' => Http::response([], 201)]);

        Mail::raw('Hola', fn ($m) => $m->to('ana@ejemplo.com')->subject('Prueba'));

        Http::assertSent(fn (Request $r) => $r['sender'] === ['email' => 'reservas@ejemplo.com', 'name' => self::NOMBRE]);
    }

    /** @return array<string, array{0: object, 1: string}> */
    public static function notificaciones(): array
    {
        return [
            'codigo de acceso'    => [new CodigoVerificacion('123456', 10), 'Tu código de acceso · '.self::NOMBRE],
            'codigo recuperacion' => [new CodigoRecuperacion('123456', 15), 'Recupera tu acceso · '.self::NOMBRE],
            'restablecer'         => [new RestablecerContrasena('token'), 'Restablece tu contraseña · '.self::NOMBRE],
        ];
    }

    #[Test]
    #[DataProvider('notificaciones')]
    public function las_notificaciones_llevan_el_nombre_en_asunto_encabezado_y_pie(object $notificacion, string $asunto): void
    {
        config(['app.name' => 'Reservas Corferias']);
        NombrePublico::aplicar();
        $usuario = User::factory()->create();

        $correo = $notificacion->toMail($usuario);
        $html   = (string) $correo->render();

        $this->assertSame($asunto, $correo->subject);
        $this->assertStringContainsString('© '.date('Y').' '.self::NOMBRE.'. Todos los derechos reservados.', $html);
        $this->assertGreaterThanOrEqual(2, substr_count($html, self::NOMBRE), 'Falta el nombre en el encabezado o el pie');
        $this->assertStringNotContainsStringIgnoringCase('corferias', $html);
    }

    #[Test]
    public function el_registro_envia_la_confirmacion_en_espanol_con_el_nombre(): void
    {
        Notification::fake();
        $usuario = User::factory()->unverified()->create();

        $usuario->sendEmailVerificationNotification();

        Notification::assertSentTo($usuario, ConfirmarCorreo::class, function (ConfirmarCorreo $n) use ($usuario) {
            $correo = $n->toMail($usuario);

            return $correo->subject === 'Confirma tu correo · '.self::NOMBRE
                && str_contains($correo->actionUrl, '/correo/verificar/');
        });
    }

    #[Test]
    public function los_correos_de_reservas_y_eventos_llevan_el_nombre_en_asunto_encabezado_y_pie(): void
    {
        $escenario = Escenario::factory()->create(['slug' => 'alfa']);
        $reserva   = Reserva::factory()->for($escenario)->create();
        $evento    = Evento::factory()->create(['escenario_id' => $escenario->id]);

        foreach ([new ReservaRegistrada($reserva), new EventoPublicado($evento, Suscriptor::create(['email' => 'lector@ejemplo.com']))] as $correo) {
            $this->assertStringEndsWith(' · '.self::NOMBRE, $correo->envelope()->subject);
            $html = $correo->render();
            $this->assertStringContainsString(mb_strtoupper(self::NOMBRE), $html);
            $this->assertStringContainsString('<strong>'.self::NOMBRE.'</strong>', $html);
            $this->assertStringNotContainsStringIgnoringCase('corferias', $html);
        }
    }

    #[Test]
    public function el_qr_de_la_app_autenticadora_usa_el_nombre_descriptivo_como_emisor(): void
    {
        $usuario = User::factory()->create(['email' => 'ana@ejemplo.com']);

        $uri = app(DosPasos::class)->uriTotp($usuario, 'JBSWY3DPEHPK3PXP');

        $this->assertStringStartsWith('otpauth://totp/Reservas%20de%20recinto%20ferial:ana%40ejemplo.com?', $uri);
        $this->assertStringContainsString('&issuer=Reservas%20de%20recinto%20ferial&', $uri);
    }
}

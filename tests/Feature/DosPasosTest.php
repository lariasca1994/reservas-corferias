<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\CodigoVerificacion;
use App\Services\DosPasos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Verificacion en dos pasos: obligatoria al ingresar, con app autenticadora o
 * codigo por correo, y el otro metodo agregable desde Seguridad.
 */
class DosPasosTest extends TestCase
{
    use RefreshDatabase;

    private function ingresar(User $u): void
    {
        $this->post(route('login'), ['email' => $u->email, 'password' => 'clave-de-prueba'])
            ->assertRedirect(route('verificacion'));
    }

    private function usuario(): User
    {
        return User::factory()->create(['password' => bcrypt('clave-de-prueba')]);
    }

    /** Configura la app autenticadora en el primer ingreso y devuelve su clave. */
    private function configurarApp(User $u): string
    {
        $this->ingresar($u);
        $app = $this->post(route('verificacion.configurar'), ['metodo' => 'totp'])->viewData('app');
        $this->post(route('verificacion.activar'), ['metodo' => 'totp', 'codigo' => DosPasos::totp($app['secreto'], intdiv(time(), 30))])
            ->assertRedirect(route('mis-reservas.index'));
        $this->post(route('logout'));

        return $app['secreto'];
    }

    #[Test]
    public function la_contrasena_sola_no_abre_sesion(): void
    {
        $this->ingresar($this->usuario());
        $this->assertGuest();
        $this->get(route('verificacion'))->assertOk()->assertSee('App autenticadora');
    }

    #[Test]
    public function sin_paso_pendiente_la_verificacion_vuelve_al_login(): void
    {
        $this->get(route('verificacion'))->assertRedirect(route('login'));
    }

    #[Test]
    public function un_codigo_errado_no_activa_la_app(): void
    {
        $u = $this->usuario();
        $this->ingresar($u);
        $this->post(route('verificacion.configurar'), ['metodo' => 'totp']);
        $this->post(route('verificacion.activar'), ['metodo' => 'totp', 'codigo' => '000000'])
            ->assertRedirect(route('verificacion'));
        $this->assertGuest();
        $this->assertSame([], app(DosPasos::class)->activos($u->fresh()));
    }

    #[Test]
    public function la_clave_de_la_app_se_guarda_cifrada_y_no_se_expone(): void
    {
        $u       = $this->usuario();
        $secreto = $this->configurarApp($u);
        $u->refresh();
        $this->assertStringNotContainsString($secreto, json_encode($u->mfa));
        $this->assertArrayNotHasKey('mfa', $u->toArray());
    }

    #[Test]
    public function un_codigo_de_la_app_no_sirve_dos_veces_y_cinco_errados_la_bloquean(): void
    {
        $u       = $this->usuario();
        $secreto = $this->configurarApp($u);
        $usado   = DosPasos::totp($secreto, intdiv(time(), 30));

        $this->ingresar($u);
        $this->post(route('verificacion.verificar'), ['metodo' => 'totp', 'codigo' => $usado]);
        $this->assertGuest();
        foreach (range(1, 4) as $_) {
            $this->post(route('verificacion.verificar'), ['metodo' => 'totp', 'codigo' => '000000']);
        }
        // Bloqueada: ni siquiera el codigo siguiente valido entra.
        $this->post(route('verificacion.verificar'), ['metodo' => 'totp', 'codigo' => DosPasos::totp($secreto, intdiv(time(), 30) + 1)]);
        $this->assertGuest();
    }

    #[Test]
    public function desde_seguridad_se_agrega_el_correo_y_se_entra_con_el(): void
    {
        $u = $this->usuario();
        $this->configurarApp($u);
        $u = $u->fresh();
        Notification::fake();

        $this->actingAs($u)->post(route('cuenta.seguridad.agregar'), ['metodo' => 'correo'])->assertOk();
        $codigo = null;
        Notification::assertSentTo($u, CodigoVerificacion::class, function ($n) use (&$codigo) {
            $codigo = $n->codigo;

            return true;
        });
        $this->post(route('cuenta.seguridad.activar'), ['metodo' => 'correo', 'codigo' => $codigo])
            ->assertRedirect(route('cuenta.seguridad'));
        $this->post(route('cuenta.seguridad.preferido'), ['metodo' => 'correo']);
        $this->assertSame(['totp', 'correo'], app(DosPasos::class)->activos($u->fresh()));
        $this->post(route('logout'));

        // Con el correo como preferido, el ingreso envia el codigo solo.
        Notification::fake();
        $this->ingresar($u);
        Notification::assertSentTo($u, CodigoVerificacion::class, function ($n) use (&$codigo) {
            $codigo = $n->codigo;

            return true;
        });
        $this->post(route('verificacion.verificar'), ['metodo' => 'correo', 'codigo' => $codigo])
            ->assertRedirect(route('mis-reservas.index'));
        $this->assertAuthenticatedAs($u);
    }
}

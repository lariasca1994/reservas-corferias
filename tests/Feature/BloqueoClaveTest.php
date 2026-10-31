<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DosPasos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BloqueoClaveTest extends TestCase
{
    use RefreshDatabase;

    private function intentar(string $email, string $clave)
    {
        // Se limpia el freno por conexión para probar solo el bloqueo por cuenta.
        RateLimiter::clear('login:'.strtolower($email).'|127.0.0.1');

        return $this->post(route('login'), ['email' => $email, 'password' => $clave]);
    }

    #[Test]
    public function cinco_contrasenas_erradas_seguidas_bloquean_la_cuenta(): void
    {
        $usuario = User::factory()->create(['password' => 'Clave-correcta-2026']);

        foreach (range(1, 5) as $intento) {
            $this->intentar($usuario->email, 'Clave-errada-'.$intento);
        }

        $this->assertTrue(app(DosPasos::class)->claveBloqueada($usuario->fresh()));
        $this->intentar($usuario->email, 'Clave-correcta-2026')
            ->assertSessionHasErrors(['email' => DosPasos::MENSAJE_BLOQUEO]);
        $this->assertSame(5, DB::table('eventos_seguridad')->where('tipo', 'clave_errada')->count());
        $this->assertSame(1, DB::table('eventos_seguridad')->where('tipo', 'cuenta_bloqueada')->count());
    }

    #[Test]
    public function la_contrasena_correcta_reinicia_el_contador(): void
    {
        $usuario = User::factory()->create(['password' => 'Clave-correcta-2026']);

        foreach (range(1, 4) as $intento) {
            $this->intentar($usuario->email, 'Clave-errada-'.$intento);
        }
        $this->intentar($usuario->email, 'Clave-correcta-2026');
        $this->intentar($usuario->email, 'Clave-errada-otra');

        $this->assertFalse(app(DosPasos::class)->claveBloqueada($usuario->fresh()));
        $this->assertSame(1, $usuario->fresh()->mfa['fallos_clave']);
    }

    #[Test]
    public function restablecer_la_contrasena_levanta_el_bloqueo_y_queda_registrado(): void
    {
        $usuario = User::factory()->create(['mfa' => ['bloqueo_clave_hasta' => time() + 600]]);

        app(DosPasos::class)->claveRestablecida($usuario);

        $this->assertFalse(app(DosPasos::class)->claveBloqueada($usuario->fresh()));
        $this->assertSame(1, DB::table('eventos_seguridad')->where('tipo', 'clave_cambiada')->count());
    }
}

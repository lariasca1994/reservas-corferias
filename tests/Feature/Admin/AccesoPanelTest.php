<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Control de acceso al panel de gestion.
 *
 * El sistema de 2019 no tenia autenticacion de ninguna clase: cualquiera
 * que conociera una URL podia usarla. Estas pruebas fijan quien entra y
 * quien no, para que no se pueda aflojar por descuido.
 */
class AccesoPanelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function un_visitante_sin_sesion_es_enviado_al_login(): void
    {
        $this->get('/panel')->assertRedirect(route('login'));
    }

    #[Test]
    public function un_cliente_no_puede_entrar_al_panel(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/panel')
            ->assertForbidden();
    }

    #[Test]
    public function una_cuenta_desactivada_no_puede_entrar_aunque_tenga_rol(): void
    {
        $this->actingAs(User::factory()->operador()->inactivo()->create())
            ->get('/panel')
            ->assertForbidden();
    }

    #[Test]
    public function un_operador_entra_al_panel(): void
    {
        $this->actingAs(User::factory()->operador()->create())
            ->get('/panel')
            ->assertOk();
    }

    #[Test]
    public function un_operador_no_puede_gestionar_usuarios(): void
    {
        $this->actingAs(User::factory()->operador()->create())
            ->get(route('admin.usuarios.index'))
            ->assertForbidden();
    }

    #[Test]
    public function un_administrador_si_puede_gestionar_usuarios(): void
    {
        $this->actingAs(User::factory()->administrador()->create())
            ->get(route('admin.usuarios.index'))
            ->assertOk();
    }

    #[Test]
    public function el_login_rechaza_credenciales_incorrectas(): void
    {
        User::factory()->create(['email' => 'ana@ejemplo.com']);

        $this->post(route('login'), [
            'email'    => 'ana@ejemplo.com',
            'password' => 'clave-equivocada',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    #[Test]
    public function el_login_rechaza_una_cuenta_desactivada(): void
    {
        User::factory()->inactivo()->create([
            'email'    => 'ana@ejemplo.com',
            'password' => bcrypt('clave-de-prueba'),
        ]);

        $this->post(route('login'), [
            'email'    => 'ana@ejemplo.com',
            'password' => 'clave-de-prueba',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    #[Test]
    public function un_gestor_es_llevado_al_panel_tras_iniciar_sesion(): void
    {
        User::factory()->administrador()->create([
            'email'    => 'admin@ejemplo.com',
            'password' => bcrypt('clave-de-prueba'),
        ]);

        $this->post(route('login'), [
            'email'    => 'admin@ejemplo.com',
            'password' => 'clave-de-prueba',
        ])->assertRedirect(route('admin.panel'));

        $this->assertAuthenticated();
    }
}

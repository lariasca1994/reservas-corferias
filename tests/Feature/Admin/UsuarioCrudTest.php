<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UsuarioCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->administrador()->create();
    }

    /**
     * @return array<string, mixed>
     */
    private function datos(array $sobrescribir = []): array
    {
        return array_merge([
            'name'                  => 'Carolina Ruiz',
            'email'                 => 'carolina@ejemplo.com',
            'telefono'              => '3001112233',
            'rol'                   => User::ROL_OPERADOR,
            'activo'                => true,
            'password'              => 'clave-segura-2026',
            'password_confirmation' => 'clave-segura-2026',
        ], $sobrescribir);
    }

    #[Test]
    public function crea_una_cuenta_con_la_contrasena_cifrada(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.usuarios.store'), $this->datos())
            ->assertRedirect(route('admin.usuarios.index'));

        $usuario = User::where('email', 'carolina@ejemplo.com')->firstOrFail();

        $this->assertSame(User::ROL_OPERADOR, $usuario->rol);
        $this->assertNotSame('clave-segura-2026', $usuario->password);
        $this->assertTrue(Hash::check('clave-segura-2026', $usuario->password));
    }

    #[Test]
    public function rechaza_una_contrasena_debil(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.usuarios.store'), $this->datos([
                'password'              => '12345',
                'password_confirmation' => '12345',
            ]))
            ->assertSessionHasErrors('password');
    }

    #[Test]
    public function rechaza_un_correo_ya_registrado(): void
    {
        User::factory()->create(['email' => 'carolina@ejemplo.com']);

        $this->actingAs($this->admin)
            ->post(route('admin.usuarios.store'), $this->datos())
            ->assertSessionHasErrors('email');
    }

    #[Test]
    public function al_editar_sin_contrasena_se_conserva_la_anterior(): void
    {
        $usuario = User::factory()->operador()->create();
        $antes   = $usuario->password;

        $this->actingAs($this->admin)->put(
            route('admin.usuarios.update', $usuario),
            $this->datos([
                'email'                 => $usuario->email,
                'password'              => '',
                'password_confirmation' => '',
            ])
        );

        $this->assertSame($antes, $usuario->fresh()->password);
    }

    #[Test]
    public function un_administrador_no_puede_degradar_su_propia_cuenta(): void
    {
        $this->actingAs($this->admin)->put(
            route('admin.usuarios.update', $this->admin),
            $this->datos([
                'email'  => $this->admin->email,
                'rol'    => User::ROL_CLIENTE,
                'activo' => false,
            ])
        );

        $this->admin->refresh();

        $this->assertSame(User::ROL_ADMINISTRADOR, $this->admin->rol);
        $this->assertTrue($this->admin->activo);
    }

    #[Test]
    public function no_se_puede_eliminar_al_unico_administrador_activo(): void
    {
        $otro = User::factory()->administrador()->create();

        // Con dos administradores, el borrado procede.
        $this->actingAs($this->admin)
            ->delete(route('admin.usuarios.destroy', $otro))
            ->assertSessionHas('exito');

        $this->assertDatabaseMissing('users', ['id' => $otro->id]);
    }

    #[Test]
    public function nadie_puede_eliminar_su_propia_cuenta(): void
    {
        $this->actingAs($this->admin)
            ->delete(route('admin.usuarios.destroy', $this->admin))
            ->assertSessionHas('aviso');

        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }
}

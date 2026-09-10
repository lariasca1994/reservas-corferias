<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GuardarUsuarioRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Gestion de cuentas. Solo accesible para administradores.
 */
class UsuarioAdminController extends Controller
{
    public function index(): View
    {
        return view('admin.usuarios.index', [
            'usuarios' => User::orderBy('rol')->orderBy('name')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.usuarios.form', ['usuario' => null]);
    }

    public function store(GuardarUsuarioRequest $request): RedirectResponse
    {
        $usuario = User::create($request->validated());

        return redirect()
            ->route('admin.usuarios.index')
            ->with('exito', "La cuenta de {$usuario->name} quedó creada.");
    }

    public function edit(User $usuario): View
    {
        return view('admin.usuarios.form', ['usuario' => $usuario]);
    }

    public function update(GuardarUsuarioRequest $request, User $usuario): RedirectResponse
    {
        $datos = $request->validated();

        // Sin contrasena nueva se conserva la actual.
        if (blank($datos['password'] ?? null)) {
            unset($datos['password']);
        }

        // Nadie puede quitarse a si mismo el rol de administrador ni
        // desactivar su propia cuenta: dejaria el sistema sin acceso.
        if ($usuario->is($request->user())) {
            $datos['rol']    = $usuario->rol;
            $datos['activo'] = true;
        }

        $usuario->update($datos);

        return redirect()
            ->route('admin.usuarios.index')
            ->with('exito', "La cuenta de {$usuario->name} quedó actualizada.");
    }

    public function destroy(Request $request, User $usuario): RedirectResponse
    {
        if ($usuario->is($request->user())) {
            return redirect()
                ->route('admin.usuarios.index')
                ->with('aviso', 'No puedes eliminar tu propia cuenta.');
        }

        // Si es el ultimo administrador activo, se bloquea el borrado.
        $esUltimoAdmin = $usuario->esAdministrador()
            && User::where('rol', User::ROL_ADMINISTRADOR)->where('activo', true)->count() === 1;

        if ($esUltimoAdmin) {
            return redirect()
                ->route('admin.usuarios.index')
                ->with('aviso', 'No puedes eliminar al único administrador activo.');
        }

        // Las reservas conservan su historial: user_id queda en null por la
        // restriccion nullOnDelete definida en la migracion.
        $usuario->delete();

        return redirect()
            ->route('admin.usuarios.index')
            ->with('exito', 'La cuenta se eliminó.');
    }
}

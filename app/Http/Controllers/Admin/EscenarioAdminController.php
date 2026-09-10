<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GuardarEscenarioRequest;
use App\Models\Escenario;
use App\Services\AlmacenamientoImagenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EscenarioAdminController extends Controller
{
    private const CARPETA = 'escenarios';

    public function __construct(
        private readonly AlmacenamientoImagenService $imagenes,
    ) {}

    public function index(): View
    {
        return view('admin.escenarios.index', [
            'escenarios' => Escenario::withCount('reservas')->orderBy('nombre')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('admin.escenarios.form', ['escenario' => null]);
    }

    public function store(GuardarEscenarioRequest $request): RedirectResponse
    {
        $datos = $request->safe()->except(['imagen', 'caracteristicas']);

        $datos['imagen_principal'] = $this->imagenes->guardar(
            $request->file('imagen'),
            self::CARPETA
        );

        $escenario = Escenario::create($datos);

        $this->sincronizarCaracteristicas($escenario, $request->input('caracteristicas', []));

        return redirect()
            ->route('admin.escenarios.index')
            ->with('exito', "El escenario «{$escenario->nombre}» quedó creado.");
    }

    public function edit(Escenario $escenario): View
    {
        $escenario->load(['caracteristicas', 'imagenes']);

        return view('admin.escenarios.form', ['escenario' => $escenario]);
    }

    public function update(GuardarEscenarioRequest $request, Escenario $escenario): RedirectResponse
    {
        $datos = $request->safe()->except(['imagen', 'caracteristicas']);

        if ($request->hasFile('imagen')) {
            $datos['imagen_principal'] = $this->imagenes->reemplazar(
                $request->file('imagen'),
                self::CARPETA,
                $escenario->imagen_principal
            );
        }

        $escenario->update($datos);

        $this->sincronizarCaracteristicas($escenario, $request->input('caracteristicas', []));

        return redirect()
            ->route('admin.escenarios.index')
            ->with('exito', "El escenario «{$escenario->nombre}» quedó actualizado.");
    }

    /**
     * No se borra un escenario con reservas: se desactiva.
     *
     * Eliminarlo arrastraria en cascada su historial de reservas, que es
     * informacion contable y de trazabilidad que no debe perderse.
     */
    public function destroy(Escenario $escenario): RedirectResponse
    {
        if ($escenario->reservas()->exists()) {
            $escenario->update(['activo' => false]);

            return redirect()
                ->route('admin.escenarios.index')
                ->with('aviso', "«{$escenario->nombre}» tiene reservas asociadas, así que se desactivó en lugar de eliminarse.");
        }

        $this->imagenes->eliminar($escenario->imagen_principal);

        // Carga explicita: preventLazyLoading esta activo fuera de produccion.
        $escenario->loadMissing('imagenes');
        $escenario->imagenes->each(fn ($imagen) => $this->imagenes->eliminar($imagen->ruta));
        $escenario->delete();

        return redirect()
            ->route('admin.escenarios.index')
            ->with('exito', 'El escenario se eliminó.');
    }

    /**
     * @param  array<int, array{titulo: string, descripcion: string}>  $caracteristicas
     */
    private function sincronizarCaracteristicas(Escenario $escenario, array $caracteristicas): void
    {
        $escenario->caracteristicas()->delete();

        foreach (array_values($caracteristicas) as $orden => $item) {
            $escenario->caracteristicas()->create([
                'titulo'      => $item['titulo'],
                'descripcion' => $item['descripcion'],
                'orden'       => $orden,
            ]);
        }
    }
}

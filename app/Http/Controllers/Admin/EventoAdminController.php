<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GuardarEventoRequest;
use App\Models\Escenario;
use App\Models\Evento;
use App\Services\AlmacenamientoImagenService;
use App\Services\DifusionEventoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EventoAdminController extends Controller
{
    private const CARPETA = 'eventos';

    public function __construct(
        private readonly AlmacenamientoImagenService $imagenes,
        private readonly DifusionEventoService $difusion,
    ) {}

    public function index(): View
    {
        return view('admin.eventos.index', [
            'eventos' => Evento::with('escenario')->orderByDesc('fecha_inicio')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('admin.eventos.form', [
            'evento'     => null,
            'escenarios' => Escenario::activos()->orderBy('nombre')->get(),
        ]);
    }

    public function store(GuardarEventoRequest $request): RedirectResponse
    {
        $datos = $request->safe()->except('imagen');

        $datos['imagen'] = $this->imagenes->guardar(
            $request->file('imagen'),
            self::CARPETA
        );

        $evento = Evento::create($datos);

        return redirect()
            ->route('admin.eventos.index')
            ->with('exito', "El evento «{$evento->nombre}» quedó creado.");
    }

    public function edit(Evento $evento): View
    {
        return view('admin.eventos.form', [
            'evento'     => $evento,
            'escenarios' => Escenario::activos()->orderBy('nombre')->get(),
        ]);
    }

    public function update(GuardarEventoRequest $request, Evento $evento): RedirectResponse
    {
        $datos = $request->safe()->except('imagen');

        if ($request->hasFile('imagen')) {
            $datos['imagen'] = $this->imagenes->reemplazar(
                $request->file('imagen'),
                self::CARPETA,
                $evento->imagen
            );
        }

        $evento->update($datos);

        return redirect()
            ->route('admin.eventos.index')
            ->with('exito', "El evento «{$evento->nombre}» quedó actualizado.");
    }

    /**
     * Envia el aviso del evento a la lista de distribucion.
     *
     * Es una accion explicita y no automatica al crear el evento: un
     * correo masivo no se puede deshacer, y un evento recien creado suele
     * necesitar correcciones antes de anunciarse.
     */
    public function notificar(Evento $evento): RedirectResponse
    {
        $enviados = $this->difusion->notificar($evento);

        if ($enviados === 0) {
            return back()->with('aviso', 'No hay suscriptores activos a quienes avisar.');
        }

        return back()->with(
            'exito',
            "Aviso encolado para {$enviados} ".Str::plural('suscriptor', $enviados).'.'
        );
    }

    public function destroy(Evento $evento): RedirectResponse
    {
        $this->imagenes->eliminar($evento->imagen);
        $evento->delete();

        return redirect()
            ->route('admin.eventos.index')
            ->with('exito', 'El evento se eliminó.');
    }
}

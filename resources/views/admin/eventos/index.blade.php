@extends('layout.panel')

@section('titulo', 'Eventos')

@section('contenido')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h1 class="h3 mb-0">Eventos</h1>
        <a href="{{ route('admin.eventos.create') }}" class="btn btn-principal">Nuevo evento</a>
    </div>

    <div class="tarjeta">
        <div class="table-responsive">
            <table class="table tabla-panel align-middle mb-0">
                <thead>
                    <tr><th></th><th>Nombre</th><th>Fechas</th><th>Escenario</th><th>Destacado</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($eventos as $evento)
                        <tr>
                            <td style="width:72px;">
                                <img src="{{ app(\App\Services\AlmacenamientoImagenService::class)->url($evento->imagen) }}"
                                     alt="" width="56" height="40" style="object-fit:cover; border-radius:4px;">
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $evento->nombre }}</div>
                                <div class="text-muted small">{{ $evento->slug }}</div>
                            </td>
                            <td>{{ $evento->fecha_inicio->format('d/m/Y') }} — {{ $evento->fecha_fin->format('d/m/Y') }}</td>
                            <td>{{ $evento->escenario?->nombre ?? '—' }}</td>
                            <td>
                                <span class="etiqueta {{ $evento->destacado ? 'etiqueta--confirmada' : 'etiqueta--neutra' }}">
                                    {{ $evento->destacado ? 'Sí' : 'No' }}
                                </span>
                            </td>
                            <td class="text-end text-nowrap">
                                <form method="POST" action="{{ route('admin.eventos.notificar', $evento) }}" class="d-inline"
                                      onsubmit="return confirm('¿Enviar el aviso de «{{ $evento->nombre }}» a toda la lista de distribución? Esta acción no se puede deshacer.');">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-primary">Notificar</button>
                                </form>
                                <a href="{{ route('admin.eventos.edit', $evento) }}" class="btn btn-sm btn-outline-secondary">Editar</a>
                                <form method="POST" action="{{ route('admin.eventos.destroy', $evento) }}" class="d-inline"
                                      onsubmit="return confirm('¿Eliminar «{{ $evento->nombre }}»?');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Aún no hay eventos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $eventos->links() }}</div>
@endsection

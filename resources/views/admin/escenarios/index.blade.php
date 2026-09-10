@extends('layout.panel')

@section('titulo', 'Escenarios')

@section('contenido')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h1 class="h3 mb-0">Escenarios</h1>
        <a href="{{ route('admin.escenarios.create') }}" class="btn btn-principal">Nuevo escenario</a>
    </div>

    <div class="tarjeta">
        <div class="table-responsive">
            <table class="table tabla-panel align-middle mb-0">
                <thead>
                    <tr><th></th><th>Nombre</th><th>Capacidad</th><th>Precio/día</th><th>Reservas</th><th>Estado</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($escenarios as $escenario)
                        <tr>
                            <td style="width:72px;">
                                <img src="{{ app(\App\Services\AlmacenamientoImagenService::class)->url($escenario->imagen_principal) }}"
                                     alt="" width="56" height="40" style="object-fit:cover; border-radius:4px;">
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $escenario->nombre }}</div>
                                <div class="text-muted small">{{ $escenario->slug }}</div>
                            </td>
                            <td>{{ number_format($escenario->capacidad, 0, ',', '.') }}</td>
                            <td>{{ $escenario->precioFormateado() }}</td>
                            <td>{{ $escenario->reservas_count }}</td>
                            <td>
                                <span class="etiqueta {{ $escenario->activo ? 'etiqueta--confirmada' : 'etiqueta--neutra' }}">
                                    {{ $escenario->activo ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('admin.escenarios.edit', $escenario) }}" class="btn btn-sm btn-outline-secondary">Editar</a>
                                <form method="POST" action="{{ route('admin.escenarios.destroy', $escenario) }}" class="d-inline"
                                      onsubmit="return confirm('¿Eliminar «{{ $escenario->nombre }}»? Si tiene reservas, solo se desactivará.');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">Aún no hay escenarios.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $escenarios->links() }}</div>
@endsection

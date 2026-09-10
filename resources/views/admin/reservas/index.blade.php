@extends('layout.panel')

@section('titulo', 'Reservas')

@section('contenido')
    <h1 class="h3 mb-4">Reservas</h1>

    <form method="GET" class="tarjeta p-3 mb-4">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label for="buscar" class="form-label">Buscar</label>
                <input type="search" id="buscar" name="buscar" class="form-control"
                       value="{{ request('buscar') }}" placeholder="Código, nombre o correo">
            </div>
            <div class="col-md-3">
                <label for="estado" class="form-label">Estado</label>
                <select id="estado" name="estado" class="form-select">
                    <option value="">Todos</option>
                    @foreach ($estados as $estado)
                        <option value="{{ $estado }}" @selected(request('estado') === $estado)>
                            {{ ucfirst($estado) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="escenario" class="form-label">Escenario</label>
                <select id="escenario" name="escenario" class="form-select">
                    <option value="">Todos</option>
                    @foreach ($escenarios as $escenario)
                        <option value="{{ $escenario->id }}" @selected(request('escenario') == $escenario->id)>
                            {{ $escenario->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button class="btn btn-principal">Filtrar</button>
            </div>
        </div>
    </form>

    <div class="tarjeta">
        <div class="table-responsive">
            <table class="table tabla-panel align-middle mb-0">
                <thead>
                    <tr><th>Código</th><th>Escenario</th><th>Fechas</th><th>Contacto</th><th>Estado</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($reservas as $reserva)
                        <tr>
                            <td class="fw-semibold">{{ $reserva->codigo }}</td>
                            <td>{{ $reserva->escenario->nombre }}</td>
                            <td>{{ $reserva->fecha_inicio->format('d/m/Y') }} — {{ $reserva->fecha_fin->format('d/m/Y') }}</td>
                            <td>
                                {{ $reserva->nombre_contacto }}<br>
                                <span class="text-muted small">{{ $reserva->email_contacto }}</span>
                            </td>
                            <td><x-etiqueta-estado :reserva="$reserva" /></td>
                            <td class="text-end">
                                <a href="{{ route('admin.reservas.show', $reserva) }}" class="btn btn-sm btn-outline-secondary">
                                    Abrir
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Sin resultados para este filtro.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $reservas->links() }}</div>
@endsection

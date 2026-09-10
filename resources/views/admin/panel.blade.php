@extends('layout.panel')

@section('titulo', 'Resumen')

@section('contenido')
    <h1 class="h3 mb-4">Resumen</h1>

    <div class="row g-3 mb-5">
        <div class="col-sm-6 col-xl-3">
            <div class="metrica metrica--aviso">
                <div class="metrica__valor">{{ $pendientes }}</div>
                <div class="metrica__titulo">Pendientes</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="metrica">
                <div class="metrica__valor">{{ $confirmadas }}</div>
                <div class="metrica__titulo">Confirmadas</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="metrica">
                <div class="metrica__valor">{{ $escenariosActivos }}</div>
                <div class="metrica__titulo">Escenarios activos</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="metrica metrica--alerta">
                <div class="metrica__valor">{{ $eventosVigentes }}</div>
                <div class="metrica__titulo">Eventos vigentes</div>
            </div>
        </div>
    </div>

    <div class="tarjeta">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
            <h2 class="h6 mb-0">Últimas solicitudes</h2>
            <a href="{{ route('admin.reservas.index') }}" class="small text-decoration-none">Ver todas</a>
        </div>

        <div class="table-responsive">
            <table class="table tabla-panel align-middle mb-0">
                <thead>
                    <tr><th>Código</th><th>Escenario</th><th>Fechas</th><th>Contacto</th><th>Estado</th></tr>
                </thead>
                <tbody>
                    @forelse ($ultimas as $reserva)
                        <tr>
                            <td>
                                <a href="{{ route('admin.reservas.show', $reserva) }}" class="fw-semibold text-decoration-none">
                                    {{ $reserva->codigo }}
                                </a>
                            </td>
                            <td>{{ $reserva->escenario->nombre }}</td>
                            <td>{{ $reserva->fecha_inicio->format('d/m/Y') }} — {{ $reserva->fecha_fin->format('d/m/Y') }}</td>
                            <td>{{ $reserva->nombre_contacto }}</td>
                            <td><x-etiqueta-estado :reserva="$reserva" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Sin solicitudes registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

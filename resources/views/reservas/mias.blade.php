@extends('layout.publico')

@section('titulo', 'Mis reservas')

@section('contenido')
    <div class="container py-5">
        <p class="antetitulo mb-1">Tu cuenta</p>
        <h1 class="mb-4">Mis reservas</h1>

        @forelse ($reservas as $reserva)
            <div class="tarjeta mb-3">
                <div class="p-4">
                    <div class="row g-3 align-items-center">

                        <div class="col-md-3">
                            <div class="ayuda-campo">Código</div>
                            <div class="fw-semibold" style="font-family: Consolas, monospace; letter-spacing:.06em;">
                                {{ $reserva->codigo }}
                            </div>
                            <x-etiqueta-estado :reserva="$reserva" />
                        </div>

                        <div class="col-md-4">
                            <div class="ayuda-campo">Escenario</div>
                            <div class="fw-semibold">{{ $reserva->escenario->nombre }}</div>
                            <div class="text-muted small">
                                {{ $reserva->fecha_inicio->format('d/m/Y') }} al
                                {{ $reserva->fecha_fin->format('d/m/Y') }}
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="ayuda-campo">Valor</div>
                            <div class="fw-semibold">$ {{ number_format($reserva->total(), 0, ',', '.') }}</div>
                        </div>

                        <div class="col-md-3 text-md-end">
                            <a href="{{ route('reservas.show', $reserva) }}" class="btn btn-sm btn-outline-secondary mb-1">
                                Ver comprobante
                            </a>

                            @unless ($reserva->estaCancelada())
                                <button type="button" class="btn btn-sm btn-outline-danger mb-1"
                                        data-bs-toggle="modal" data-bs-target="#cancelar-{{ $reserva->id }}">
                                    Cancelar
                                </button>
                            @endunless
                        </div>
                    </div>
                </div>
            </div>

            @unless ($reserva->estaCancelada())
                <div class="modal fade" id="cancelar-{{ $reserva->id }}" tabindex="-1"
                     aria-labelledby="titulo-cancelar-{{ $reserva->id }}" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <form method="POST" action="{{ route('mis-reservas.cancelar', $reserva) }}" class="modal-content">
                            @csrf @method('PATCH')

                            <div class="modal-header">
                                <h2 class="modal-title h5" id="titulo-cancelar-{{ $reserva->id }}">
                                    Cancelar {{ $reserva->codigo }}
                                </h2>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                            </div>

                            <div class="modal-body">
                                <p class="mb-3">
                                    Las fechas volverán a quedar disponibles para otros clientes.
                                    Esta acción no se puede deshacer.
                                </p>
                                <label for="motivo-{{ $reserva->id }}" class="form-label">
                                    Motivo <span class="text-muted fw-normal">(opcional)</span>
                                </label>
                                <textarea id="motivo-{{ $reserva->id }}" name="motivo" rows="3"
                                          maxlength="500" class="form-control"></textarea>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-link text-decoration-none" data-bs-dismiss="modal">
                                    Volver
                                </button>
                                <button type="submit" class="btn btn-outline-danger">Sí, cancelar</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endunless
        @empty
            <div class="tarjeta p-5 text-center">
                <p class="mb-3 text-muted">Todavía no tienes reservas registradas.</p>
                <a href="{{ route('escenarios.index') }}" class="btn btn-principal">Ver escenarios</a>
            </div>
        @endforelse

        <div class="mt-4">{{ $reservas->links() }}</div>
    </div>
@endsection

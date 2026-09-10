@extends('layout.panel')

@section('titulo', 'Reserva '.$reserva->codigo)

@section('contenido')
    <a href="{{ route('admin.reservas.index') }}" class="small text-decoration-none">&larr; Volver a reservas</a>

    <div class="d-flex flex-wrap align-items-center gap-3 mt-2 mb-4">
        <h1 class="h3 mb-0">{{ $reserva->codigo }}</h1>
        <x-etiqueta-estado :reserva="$reserva" />
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="tarjeta p-4">
                <h2 class="h6 text-uppercase mb-3" style="letter-spacing:.08em; color: var(--gris);">Detalle</h2>

                <dl class="row mb-0">
                    <dt class="col-sm-4 fw-normal text-muted">Escenario</dt>
                    <dd class="col-sm-8 fw-semibold">{{ $reserva->escenario->nombre }}</dd>

                    <dt class="col-sm-4 fw-normal text-muted">Fechas</dt>
                    <dd class="col-sm-8">
                        {{ $reserva->fecha_inicio->format('d/m/Y') }} al {{ $reserva->fecha_fin->format('d/m/Y') }}
                        ({{ $reserva->diasReservados() }} {{ Str::plural('día', $reserva->diasReservados()) }})
                    </dd>

                    <dt class="col-sm-4 fw-normal text-muted">Contacto</dt>
                    <dd class="col-sm-8">
                        {{ $reserva->nombre_contacto }}<br>
                        <a href="mailto:{{ $reserva->email_contacto }}">{{ $reserva->email_contacto }}</a><br>
                        {{ $reserva->telefono_contacto }}
                    </dd>

                    <dt class="col-sm-4 fw-normal text-muted">Valor estimado</dt>
                    <dd class="col-sm-8 dato-destacado">$ {{ number_format($reserva->total(), 0, ',', '.') }}</dd>

                    <dt class="col-sm-4 fw-normal text-muted">Registrada</dt>
                    <dd class="col-sm-8 mb-0">{{ $reserva->created_at->format('d/m/Y H:i') }}</dd>
                </dl>

                @if ($reserva->observaciones)
                    <div class="mt-4 p-3" style="background: var(--nieve); border-radius: var(--radio-sm);">
                        <div class="fw-semibold mb-1">Observaciones</div>
                        <div class="tarjeta__texto">{{ $reserva->observaciones }}</div>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-lg-5">
            <div class="tarjeta p-4">
                <h2 class="h6 text-uppercase mb-3" style="letter-spacing:.08em; color: var(--gris);">Acciones</h2>

                @if ($reserva->estado === \App\Models\Reserva::ESTADO_PENDIENTE)
                    <form method="POST" action="{{ route('admin.reservas.confirmar', $reserva) }}" class="mb-3">
                        @csrf @method('PATCH')
                        <button class="btn btn-principal w-100">Confirmar y notificar</button>
                    </form>
                @endif

                @unless ($reserva->estaCancelada())
                    <form method="POST" action="{{ route('admin.reservas.cancelar', $reserva) }}">
                        @csrf @method('PATCH')
                        <label for="motivo" class="form-label">Motivo de cancelación</label>
                        <textarea id="motivo" name="motivo" rows="3" maxlength="500" class="form-control mb-2"
                                  placeholder="Se incluirá en el correo al cliente"></textarea>
                        <button class="btn btn-outline-danger w-100">Cancelar reserva</button>
                    </form>
                @else
                    <p class="ayuda-campo mb-0">
                        Esta reserva está cancelada. Es un estado final: para reactivarla
                        hay que crear una nueva, porque las fechas pudieron ser tomadas.
                    </p>
                @endunless

                <hr class="my-4">

                <a href="{{ route('reservas.show', $reserva) }}" target="_blank" rel="noopener"
                   class="text-decoration-none small">Ver comprobante público &rarr;</a>
            </div>
        </div>
    </div>
@endsection

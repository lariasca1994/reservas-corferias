@extends('layout.publico')

@section('titulo', 'Comprobante '.$reserva->codigo)

@section('contenido')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">

                <div class="comprobante">
                    <div class="comprobante__cabecera d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div>
                            <p class="antetitulo mb-1" style="color: var(--amarillo);">Comprobante de reserva</p>
                            <div class="comprobante__codigo">{{ $reserva->codigo }}</div>
                        </div>
                        <x-etiqueta-estado :reserva="$reserva" />
                    </div>

                    <div class="p-4 p-md-5">
                        <div class="row g-4">
                            <div class="col-md-7">
                                <dl class="row mb-0">
                                    <dt class="col-5 text-muted fw-normal">Escenario</dt>
                                    <dd class="col-7 fw-semibold">{{ $reserva->escenario->nombre }}</dd>

                                    <dt class="col-5 text-muted fw-normal">Desde</dt>
                                    <dd class="col-7">{{ $reserva->fecha_inicio->translatedFormat('d \d\e F \d\e Y') }}</dd>

                                    <dt class="col-5 text-muted fw-normal">Hasta</dt>
                                    <dd class="col-7">{{ $reserva->fecha_fin->translatedFormat('d \d\e F \d\e Y') }}</dd>

                                    <dt class="col-5 text-muted fw-normal">Días</dt>
                                    <dd class="col-7">{{ $reserva->diasReservados() }}</dd>

                                    <dt class="col-5 text-muted fw-normal">A nombre de</dt>
                                    <dd class="col-7">{{ $reserva->nombre_contacto }}</dd>

                                    <dt class="col-5 text-muted fw-normal">Valor estimado</dt>
                                    <dd class="col-7 dato-destacado">$ {{ number_format($reserva->total(), 0, ',', '.') }}</dd>
                                </dl>
                            </div>

                            @unless ($reserva->estaCancelada())
                                <div class="col-md-5 text-center comprobante__qr">
                                    {!! app(\App\Services\CodigoQrService::class)->svg($reserva) !!}
                                    <div class="ayuda-campo mt-2">Escanéalo para consultar tu reserva</div>
                                </div>
                            @endunless
                        </div>

                        @if ($reserva->observaciones)
                            <div class="mt-4 p-3" style="background: var(--nieve); border-radius: var(--radio-sm);">
                                <div class="fw-semibold mb-1">Observaciones</div>
                                <div class="tarjeta__texto">{{ $reserva->observaciones }}</div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2 no-imprimir">
                    <button onclick="window.print()" class="btn btn-principal">Imprimir</button>
                    <a href="{{ route('escenarios.index') }}" class="btn btn-link text-decoration-none">Ver otros escenarios</a>
                </div>
            </div>
        </div>
    </div>
@endsection

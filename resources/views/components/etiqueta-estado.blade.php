@props(['reserva'])

@php
    $clase = match ($reserva->estado) {
        \App\Models\Reserva::ESTADO_CONFIRMADA => 'etiqueta--confirmada',
        \App\Models\Reserva::ESTADO_CANCELADA  => 'etiqueta--cancelada',
        default                                => 'etiqueta--pendiente',
    };
@endphp

<span class="etiqueta {{ $clase }}">{{ $reserva->etiquetaEstado() }}</span>

@extends('emails.layout')

@section('titulo', 'Reserva cancelada')

@section('contenido')
    <p style="margin:0 0 18px; font-size:15px; line-height:23px;">
        Hola {{ $reserva->nombre_contacto }},
    </p>

    <p style="margin:0 0 22px; font-size:15px; line-height:23px;">
        Tu reserva <strong>{{ $reserva->codigo }}</strong> quedó cancelada y las fechas
        volvieron a estar disponibles para otros clientes.
    </p>

    @if ($reserva->observaciones)
        <p style="margin:0 0 22px; padding:14px 16px; background-color:#f4f7f7; border-radius:6px; font-size:14px; line-height:22px;">
            {{ $reserva->observaciones }}
        </p>
    @endif

    <p style="margin:0 0 22px; font-size:14px; line-height:22px; color:#5f757e;">
        Si se trató de un error, escríbenos respondiendo este mensaje y la reactivamos
        siempre que las fechas sigan libres.
    </p>

    @include('emails.reservas._detalle', ['mostrarQr' => false])
@endsection

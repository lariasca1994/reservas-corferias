@extends('emails.layout')

@section('titulo', 'Tu reserva está confirmada')

@section('contenido')
    <p style="margin:0 0 18px; font-size:15px; line-height:23px;">
        Hola {{ $reserva->nombre_contacto }},
    </p>

    <p style="margin:0 0 22px; font-size:15px; line-height:23px;">
        Confirmamos tu reserva del <strong>{{ $escenario->nombre }}</strong>.
        Guarda este correo: el código QR es tu acceso al proceso de montaje.
    </p>

    <p style="margin:0 0 22px; font-size:14px; line-height:22px; color:#5f757e;">
        Preséntalo impreso o desde el celular en la portería de servicio el día del ingreso.
    </p>

    @include('emails.reservas._detalle')
@endsection

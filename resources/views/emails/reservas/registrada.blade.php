@extends('emails.layout')

@section('titulo', 'Recibimos tu solicitud')

@section('contenido')
    <p style="margin:0 0 18px; font-size:15px; line-height:23px;">
        Hola {{ $reserva->nombre_contacto }},
    </p>

    <p style="margin:0 0 22px; font-size:15px; line-height:23px;">
        Tu solicitud quedó registrada y está <strong>pendiente de confirmación</strong>.
        Un asesor comercial revisará la disponibilidad y te escribirá dentro de las
        próximas 24 horas hábiles.
    </p>

    <p style="margin:0 0 22px; font-size:14px; line-height:22px; color:#5f757e;">
        Las fechas ya quedaron bloqueadas a tu nombre mientras se completa la revisión,
        así que nadie más puede tomarlas.
    </p>

    @include('emails.reservas._detalle')
@endsection

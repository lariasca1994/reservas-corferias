@extends('emails.layout')

@section('titulo', 'Nuevo evento en la agenda')

@section('contenido')
    <p style="margin:0 0 22px; font-size:15px; line-height:23px;">
        Acabamos de publicar un evento que quizá te interese.
    </p>

    <h2 style="margin:0 0 8px; font-family:Cambria,Georgia,serif; font-size:22px; color:#16303a;">
        {{ $evento->nombre }}
    </h2>

    <p style="margin:0 0 20px; font-size:14px; line-height:22px; color:#5f757e;">
        {{ $evento->resumen }}
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;">
        <tr>
            <td style="padding:8px 0; color:#5f757e; width:35%;">Fechas</td>
            <td style="padding:8px 0;">
                {{ $evento->fecha_inicio->translatedFormat('d \d\e F') }} al
                {{ $evento->fecha_fin->translatedFormat('d \d\e F \d\e Y') }}
            </td>
        </tr>
        <tr>
            <td style="padding:8px 0; color:#5f757e;">Horario</td>
            <td style="padding:8px 0;">{{ $evento->horario }}</td>
        </tr>
        @if ($evento->escenario)
            <tr>
                <td style="padding:8px 0; color:#5f757e;">Escenario</td>
                <td style="padding:8px 0;">{{ $evento->escenario->nombre }}</td>
            </tr>
        @endif
    </table>

    <div style="margin-top:26px;">
        <a href="{{ $urlEvento }}"
           style="display:inline-block; background-color:#e85d2a; color:#ffffff; text-decoration:none; padding:12px 22px; border-radius:6px; font-weight:bold; font-size:14px;">
            Ver el evento
        </a>
    </div>

    <p style="margin:30px 0 0; padding-top:18px; border-top:1px solid #e2eaea; font-size:11px; line-height:17px; color:#5f757e;">
        Recibes este mensaje porque te suscribiste a nuestra agenda de eventos.
        <a href="{{ $urlBaja }}" style="color:#5f757e;">Darme de baja</a>.
    </p>
@endsection

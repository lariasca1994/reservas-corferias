{{--
    Detalle de la reserva reutilizado por los tres correos.
    Recibe: $reserva, $escenario, $qrPng, $urlPublica, $mostrarQr
--}}
@php
    [$colorEstado, $fondoEstado] = match ($reserva->estado) {
        \App\Models\Reserva::ESTADO_CONFIRMADA => ['#0f7a6e', '#dcf5f1'],
        \App\Models\Reserva::ESTADO_CANCELADA  => ['#b42318', '#fde8e6'],
        default                                => ['#8a5a00', '#fff3cf'],
    };
    $dias = $reserva->diasReservados();
    $fila = 'padding:11px 14px; font-size:14px; vertical-align:top;';
    $etiqueta = $fila.' color:#5f757e; width:40%;';
    $separador = 'border-top:1px solid #e2eaea;';
@endphp

<div style="margin:0 0 12px;">
    <span style="display:inline-block; padding:4px 12px; border-radius:999px; font-size:12px; font-weight:bold; color:{{ $colorEstado }}; background-color:{{ $fondoEstado }};">
        {{ $reserva->etiquetaEstado() }}
    </span>
    <span style="display:inline-block; margin-left:6px; padding:4px 12px; border-radius:999px; font-size:12px; font-weight:bold; color:#10333b; background-color:#f4f7f7;">
        {{ $dias }} {{ Str::plural('día', $dias) }}
    </span>
</div>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e2eaea; border-radius:10px; border-collapse:separate;">
    <tr>
        <td style="{{ $etiqueta }}">Código</td>
        <td style="{{ $fila }} font-weight:bold; letter-spacing:1px;">{{ $reserva->codigo }}</td>
    </tr>
    <tr>
        <td style="{{ $etiqueta }} {{ $separador }}">Escenario</td>
        <td style="{{ $fila }} {{ $separador }} font-weight:bold;">{{ $escenario->nombre }}</td>
    </tr>
    <tr>
        <td style="{{ $etiqueta }} {{ $separador }}">Fechas</td>
        <td style="{{ $fila }} {{ $separador }}">
            {{ $reserva->fecha_inicio->translatedFormat('l j \d\e F \d\e Y') }}<br>
            al {{ $reserva->fecha_fin->translatedFormat('l j \d\e F \d\e Y') }}
        </td>
    </tr>
    <tr>
        <td style="{{ $etiqueta }} {{ $separador }}">A nombre de</td>
        <td style="{{ $fila }} {{ $separador }}">{{ $reserva->nombre_contacto }}</td>
    </tr>
    <tr>
        <td style="{{ $etiqueta }} {{ $separador }}">Valor estimado</td>
        <td style="{{ $fila }} {{ $separador }} font-weight:bold; font-size:16px;">$ {{ number_format($reserva->total(), 0, ',', '.') }}</td>
    </tr>
</table>

@if (filled($reserva->observaciones))
    <div style="margin-top:22px; font-size:12px; font-weight:bold; letter-spacing:1px; color:#17454f;">NOTAS</div>
    <div style="margin-top:8px; padding:12px 14px; background-color:#f4f7f7; border-left:3px solid #f5b301; border-radius:6px;">
        <div style="font-size:12px; color:#5f757e; padding-bottom:4px;">Observaciones de la reserva</div>
        <div style="font-size:14px; line-height:22px; color:#10333b;">{!! nl2br(e($reserva->observaciones)) !!}</div>
    </div>
@endif

@if (($mostrarQr ?? true) && $qrPng)
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:24px;">
        <tr>
            <td align="center" style="padding:20px; background-color:#f4f7f7; border:1px solid #e2eaea; border-radius:10px;">
                <img src="{{ $message->embedData($qrPng, 'reserva-'.$reserva->codigo.'.png', 'image/png') }}"
                     alt="Código QR de la reserva {{ $reserva->codigo }}"
                     width="180" height="180" style="display:block;">
                <div style="font-size:12px; color:#5f757e; padding-top:12px;">
                    Escanéalo para consultar tu reserva en cualquier momento
                </div>
            </td>
        </tr>
    </table>
@endif

<div style="margin-top:24px;">
    <a href="{{ $urlPublica }}"
       style="display:inline-block; background-color:#e85d2a; color:#ffffff; text-decoration:none; padding:12px 24px; border-radius:8px; font-weight:bold; font-size:14px;">
        Ver mi comprobante
    </a>
</div>

<div style="margin-top:14px; font-size:12px; color:#5f757e; word-break:break-all;">
    O copia este enlace: {{ $urlPublica }}
</div>

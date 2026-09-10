{{--
    Detalle de la reserva reutilizado por los tres correos.
    Recibe: $reserva, $escenario, $qrPng, $urlPublica, $mostrarQr
--}}

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; font-size:14px;">
    <tr>
        <td style="padding:8px 0; color:#5f757e; width:42%;">Código</td>
        <td style="padding:8px 0; font-weight:bold; letter-spacing:1px;">{{ $reserva->codigo }}</td>
    </tr>
    <tr>
        <td style="padding:8px 0; color:#5f757e;">Escenario</td>
        <td style="padding:8px 0;">{{ $escenario->nombre }}</td>
    </tr>
    <tr>
        <td style="padding:8px 0; color:#5f757e;">Fechas</td>
        <td style="padding:8px 0;">
            {{ $reserva->fecha_inicio->format('d/m/Y') }} al {{ $reserva->fecha_fin->format('d/m/Y') }}
            <span style="color:#5f757e;">({{ $reserva->diasReservados() }} {{ Str::plural('día', $reserva->diasReservados()) }})</span>
        </td>
    </tr>
    <tr>
        <td style="padding:8px 0; color:#5f757e;">A nombre de</td>
        <td style="padding:8px 0;">{{ $reserva->nombre_contacto }}</td>
    </tr>
    <tr>
        <td style="padding:8px 0; color:#5f757e;">Valor estimado</td>
        <td style="padding:8px 0; font-weight:bold;">$ {{ number_format($reserva->total(), 0, ',', '.') }}</td>
    </tr>
    <tr>
        <td style="padding:8px 0; color:#5f757e;">Estado</td>
        <td style="padding:8px 0;">{{ $reserva->etiquetaEstado() }}</td>
    </tr>
</table>

@if (($mostrarQr ?? true) && $qrPng)
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:26px;">
        <tr>
            <td align="center" style="padding:20px; background-color:#f4f7f7; border-radius:8px;">
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
       style="display:inline-block; background-color:#e85d2a; color:#ffffff; text-decoration:none; padding:12px 22px; border-radius:6px; font-weight:bold; font-size:14px;">
        Ver mi comprobante
    </a>
</div>

<div style="margin-top:14px; font-size:12px; color:#5f757e; word-break:break-all;">
    O copia este enlace: {{ $urlPublica }}
</div>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subject ?? config('app.name') }}</title>
</head>
{{-- Estilos en linea: los clientes de correo descartan las hojas externas --}}
<body style="margin:0; padding:0; background-color:#f4f7f7; font-family:Arial, Helvetica, sans-serif; color:#16303a;">

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f7f7; padding:24px 12px;">
        <tr>
            <td align="center">

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px; background-color:#ffffff; border-radius:10px; overflow:hidden;">

                    <tr>
                        <td style="background-color:#10333b; padding:24px 28px;">
                            <div style="color:#f5b301; font-size:11px; letter-spacing:2px; font-weight:bold;">
                                {{ strtoupper(config('app.name')) }}
                            </div>
                            <div style="color:#ffffff; font-size:22px; font-weight:bold; padding-top:6px;">
                                @yield('titulo')
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:28px;">
                            @yield('contenido')
                        </td>
                    </tr>

                    <tr>
                        <td style="background-color:#f4f7f7; padding:18px 28px; font-size:11px; color:#5f757e; line-height:17px;">
                            Este mensaje se generó automáticamente. Si no reconoces esta solicitud,
                            responde a este correo y la anulamos.
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>

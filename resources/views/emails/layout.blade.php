<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subject ?? config('app.name') }}</title>
</head>
{{-- Estilos en linea y maquetacion con tablas: los clientes de correo descartan
     las hojas externas. Paleta de public/css/estilos.css (tinta, amarillo,
     naranja, nieve). --}}
<body style="margin:0; padding:0; background-color:#f4f7f7; font-family:system-ui,-apple-system,'Segoe UI',Roboto,Arial,sans-serif; color:#10333b;">

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f7f7; padding:24px 12px;">
        <tr>
            <td align="center">

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px; background-color:#ffffff; border:1px solid #e2eaea; border-radius:10px; overflow:hidden;">

                    <tr>
                        <td style="background-color:#10333b; border-top:4px solid #f5b301; padding:22px 28px;">
                            <div style="color:#f5b301; font-size:12px; letter-spacing:2px; font-weight:bold;">
                                {{ strtoupper(config('app.name')) }}
                            </div>
                            <div style="color:#ffffff; font-size:22px; font-weight:bold; padding-top:6px; line-height:28px;">
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
                        <td style="background-color:#f4f7f7; border-top:1px solid #e2eaea; padding:18px 28px; font-size:12px; color:#5f757e; line-height:18px;">
                            @hasSection('pie')
                                @yield('pie')
                            @else
                                Este mensaje se generó automáticamente. Si no reconoces esta solicitud,
                                responde a este correo y la anulamos.
                            @endif
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>

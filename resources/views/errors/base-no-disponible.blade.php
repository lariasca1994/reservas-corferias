{{--
    Se muestra cuando la base de datos no responde (pausada o sin cuota).
    Es una vista independiente a propósito: el layout público consulta la
    sesión y el usuario, y aquí no se puede tocar la base.
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#10333B">
    <title>En mantenimiento · {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('iconos/icono.svg') }}" type="image/svg+xml">
    <style>
        :root { --fondo: #f4f7f7; --tarjeta: #ffffff; --texto: #10333b; --suave: #5f757e; --acento: #c94a1e; --icono: #18a999; --borde: #e2eaea; --franja: #10333b; }
        @media (prefers-color-scheme: dark) {
            :root { --fondo: #1a2333; --tarjeta: #161e2c; --texto: #e2e8f0; --suave: #94a3b8; --acento: #e85d2a; --icono: #18a999; --borde: #2d3a4f; --franja: #e85d2a; }
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px;
            background: radial-gradient(1100px 560px at 50% -20%, rgba(232,93,42,.12), transparent 60%), var(--fondo);
            color: var(--texto); font: 16px/1.6 'Instrument Sans', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif;
        }
        .tarjeta {
            width: 100%; max-width: 520px; background: var(--tarjeta); border: 1px solid var(--borde);
            border-radius: 18px; padding: 40px 32px; text-align: center; box-shadow: 0 20px 50px -24px rgba(16,51,59,.35);
            border-top: 5px solid var(--franja);
        }
        .icono {
            width: 64px; height: 64px; margin: 0 auto 20px; border-radius: 16px; display: grid; place-items: center;
            background: rgba(24,169,153,.13); color: var(--icono);
        }
        .marca { font-size: .8rem; letter-spacing: .12em; text-transform: uppercase; color: var(--suave); margin: 0 0 6px; }
        h1 { font-size: 1.5rem; line-height: 1.3; margin: 0 0 12px; }
        p { margin: 0 0 12px; color: var(--suave); }
        .boton {
            display: inline-block; margin-top: 12px; padding: 10px 22px; border-radius: 999px; background: var(--acento);
            color: #fff; text-decoration: none; font-weight: 600;
        }
        .boton:focus-visible { outline: 3px solid var(--texto); outline-offset: 3px; }
    </style>
</head>
<body>
    <main class="tarjeta">
        <div class="icono" aria-hidden="true">
            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><path d="m9 16 2 2 4-4"/></svg>
        </div>
        <p class="marca">{{ config('app.name') }}</p>
        <h1>Estamos en mantenimiento</h1>
        <p>Mientras preparamos el recinto, el catálogo de escenarios, la agenda de eventos y las reservas no están disponibles.</p>
        <p>Tus reservas y comprobantes están seguros. Vuelve a intentarlo más tarde.</p>
        <a class="boton" href="{{ url('/') }}">Reintentar</a>
    </main>
</body>
</html>

{{--
    404 propio: la agenda de escenarios con todas las franjas ocupadas. Vista independiente
    (no usa el layout ni consulta la base): una dirección que no existe no debe tocar datos.
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#10333B">
    <title>Página no encontrada · {{ 'Reservas de recinto ferial' }}</title>
    <link rel="icon" href="{{ asset('iconos/icono.svg') }}" type="image/svg+xml">
    <style>
        :root { --fondo: #f4f7f7; --tarjeta: #ffffff; --texto: #10333b; --suave: #5f757e; --naranja: #e85d2a; --naranja-osc: #c94a1e; --turquesa: #18a999; --borde: #e2eaea; --ocupada: #10333b; }
        @media (prefers-color-scheme: dark) {
            :root { --fondo: #1a2333; --tarjeta: #161e2c; --texto: #e2e8f0; --suave: #94a3b8; --borde: #2d3a4f; --ocupada: #e2e8f0; }
        }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px 16px; background: radial-gradient(1100px 560px at 50% -20%, rgba(232,93,42,.12), transparent 60%), var(--fondo); color: var(--texto); font: 16px/1.6 ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif; }
        main { display: grid; grid-template-columns: minmax(0, 330px) minmax(0, 1fr); gap: 40px; align-items: center; width: 100%; max-width: 820px; animation: entrar .5s cubic-bezier(.2,.8,.2,1) backwards; }
        @keyframes entrar { from { opacity: 0; transform: translateY(10px); } }
        .agenda { overflow: hidden; border: 1px solid var(--borde); border-top: 5px solid var(--naranja); border-radius: 16px; background: var(--tarjeta); box-shadow: 0 20px 50px -24px rgba(16,51,59,.35); }
        .cabeza, .pie { display: flex; justify-content: space-between; padding: 10px 16px; color: var(--suave); font-size: .82rem; }
        .cabeza { border-bottom: 1px solid var(--borde); font-family: ui-monospace, Consolas, monospace; }
        .cabeza b { color: var(--naranja-osc); }
        .pie { border-top: 1px dashed var(--borde); }
        .pie strong { color: var(--naranja-osc); }
        .rejilla { padding: 12px 16px; }
        .fila { display: grid; grid-template-columns: 78px repeat(5, 1fr); gap: 5px; margin-bottom: 6px; align-items: center; font-size: .74rem; color: var(--suave); }
        .fila i { height: 20px; border-radius: 5px; background: repeating-linear-gradient(135deg, var(--ocupada) 0 3px, transparent 3px 7px); opacity: .28; transform-origin: left; animation: llenar .45s cubic-bezier(.2,.8,.2,1) backwards; animation-delay: calc(.2s + var(--i) * 40ms); }
        @keyframes llenar { from { transform: scaleX(0); opacity: 0; } }
        .marca { margin: 0 0 8px; color: var(--naranja-osc); font-size: .78rem; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; }
        h1 { margin: 0 0 10px; font-family: Cambria, Georgia, serif; font-size: 1.9rem; line-height: 1.2; }
        p { margin: 0 0 18px; color: var(--suave); }
        code { padding: 1px 6px; border-radius: 5px; background: rgba(24,169,153,.13); color: var(--texto); font-size: .9em; overflow-wrap: anywhere; }
        .acciones { display: flex; flex-wrap: wrap; gap: 10px; }
        .boton { display: inline-flex; align-items: center; min-height: 44px; padding: 0 22px; border: 1px solid transparent; border-radius: 999px; font-weight: 600; text-decoration: none; transition: transform .18s; }
        .boton:active { transform: scale(.97); }
        .principal { background: var(--naranja-osc); color: #fff; }
        .secundario { border-color: var(--borde); color: var(--texto); }
        .boton:focus-visible { outline: 3px solid var(--texto); outline-offset: 3px; }
        @media (max-width: 720px) { main { grid-template-columns: minmax(0, 1fr); gap: 24px; } }
        @media (prefers-reduced-motion: reduce) { main, .fila i { animation: none; } }
    </style>
</head>
<body>
    <main>
        <div class="agenda" aria-hidden="true">
            <div class="cabeza"><span>GET /{{ \Illuminate\Support\Str::limit(request()->path(), 24) }}</span><b>404</b></div>
            <div class="rejilla">
                <div class="fila"><span></span><span>Lun</span><span>Mar</span><span>Mié</span><span>Jue</span><span>Vie</span></div>
                @foreach (['Pabellón 1', 'Gran Salón', 'Auditorio'] as $f => $escenario)
                    <div class="fila"><span>{{ $escenario }}</span>@for ($d = 0; $d < 5; $d++)<i style="--i: {{ $f * 5 + $d }}"></i>@endfor</div>
                @endforeach
            </div>
            <div class="pie"><span>Franja libre: ninguna</span><strong>sin escenario</strong></div>
        </div>
        <div>
            <p class="marca">Página no encontrada</p>
            <h1>Este escenario no está en la agenda</h1>
            <p>Buscamos <code>/{{ request()->path() }}</code> y no hay ninguna página reservada en esa dirección. Si tenías la sesión abierta, sigue abierta.</p>
            <div class="acciones">
                <a class="boton principal" href="{{ url('/escenarios') }}">Ver escenarios</a>
                <a class="boton secundario" href="{{ url('/') }}">Volver al inicio</a>
            </div>
        </div>
    </main>
</body>
</html>

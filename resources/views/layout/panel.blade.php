<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('titulo', 'Panel') · {{ config('app.name') }}</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous">
    <link rel="stylesheet" href="{{ asset('css/estilos.css') }}">
    @stack('estilos')
</head>
<body style="background-color: var(--nieve);">

    <div class="container-fluid">
        <div class="row">

            <aside class="col-lg-2 px-0 panel-lateral">
                <div class="px-4 pb-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <a href="{{ route('inicio') }}" class="text-white text-decoration-none fw-bold">
                            {{ config('app.name') }}
                        </a>
                        <button type="button" id="alternar-tema" class="boton-tema"
                                aria-label="Cambiar entre tema claro y oscuro" title="Cambiar tema">🌓</button>
                    </div>
                    <div class="small" style="color: rgba(255,255,255,.55);">
                        {{ auth()->user()->etiquetaRol() }}
                    </div>
                </div>

                <nav class="nav flex-column" aria-label="Navegación del panel">
                    <a class="nav-link @if(request()->routeIs('admin.panel')) activo @endif"
                       href="{{ route('admin.panel') }}">Resumen</a>
                    <a class="nav-link @if(request()->routeIs('admin.reservas.*')) activo @endif"
                       href="{{ route('admin.reservas.index') }}">Reservas</a>
                    <a class="nav-link @if(request()->routeIs('admin.escenarios.*')) activo @endif"
                       href="{{ route('admin.escenarios.index') }}">Escenarios</a>
                    <a class="nav-link @if(request()->routeIs('admin.eventos.*')) activo @endif"
                       href="{{ route('admin.eventos.index') }}">Eventos</a>
                    @if (auth()->user()->esAdministrador())
                        <a class="nav-link @if(request()->routeIs('admin.usuarios.*')) activo @endif"
                           href="{{ route('admin.usuarios.index') }}">Usuarios</a>
                    @endif
                </nav>

                <div class="px-4 pt-4 mt-4 border-top border-secondary">
                    <div class="small mb-2" style="color: rgba(255,255,255,.65);">
                        {{ auth()->user()->name }}
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn btn-contorno btn-sm w-100" type="submit">Cerrar sesión</button>
                    </form>
                </div>
            </aside>

            <main class="col-lg-10 py-4 px-lg-5">
                <x-alertas />
                @yield('contenido')
            </main>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
            crossorigin="anonymous"></script>
    <script src="{{ asset('js/tema.js') }}" defer></script>
    @stack('scripts')
</body>
</html>

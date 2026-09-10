<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="@yield('descripcion', 'Reserva escenarios y consulta la agenda de eventos del centro de convenciones.')">

    <title>@yield('titulo', 'Inicio') · {{ config('app.name') }}</title>

    {{--
        Bootstrap 5 no depende de jQuery. La version de 2019 cargaba
        bootstrap.min.js ANTES que jQuery y Popper, de modo que el menu
        movil, los desplegables y las alertas descartables no funcionaban.
    --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous">

    <link rel="stylesheet" href="{{ asset('css/estilos.css') }}">

    @stack('estilos')
</head>
<body class="d-flex flex-column min-vh-100">

    <a class="omitir-al-contenido" href="#contenido">Saltar al contenido</a>

    <nav class="navbar navbar-expand-lg barra-superior" aria-label="Navegación principal">
        <div class="container">
            <a class="navbar-brand text-white" href="{{ route('inicio') }}">
                {{ config('app.name') }}
            </a>

            <button class="navbar-toggler border-0" type="button"
                    data-bs-toggle="collapse" data-bs-target="#menu"
                    aria-controls="menu" aria-expanded="false" aria-label="Abrir menú">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="menu">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <li class="nav-item d-flex align-items-center">
                        <button type="button" id="alternar-tema" class="boton-tema"
                                aria-label="Cambiar entre tema claro y oscuro" title="Cambiar tema">🌓</button>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link @if(request()->routeIs('escenarios.*')) activo @endif"
                           href="{{ route('escenarios.index') }}">Escenarios</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link @if(request()->routeIs('eventos.*')) activo @endif"
                           href="{{ route('eventos.index') }}">Eventos</a>
                    </li>

                    @auth
                        @if (auth()->user()->puedeGestionar())
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('admin.panel') }}">Panel</a>
                            </li>
                        @else
                            <li class="nav-item">
                                <a class="nav-link @if(request()->routeIs('mis-reservas.*')) activo @endif"
                                   href="{{ route('mis-reservas.index') }}">Mis reservas</a>
                            </li>
                        @endif
                        <li class="nav-item ms-lg-2">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="btn btn-contorno btn-sm">Salir</button>
                            </form>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('registro') }}">Crear cuenta</a>
                        </li>
                        <li class="nav-item ms-lg-2">
                            <a class="btn btn-contorno btn-sm" href="{{ route('login') }}">Ingresar</a>
                        </li>
                    @endauth
                </ul>
            </div>
        </div>
    </nav>

    <main id="contenido" class="flex-grow-1">
        <x-alertas />
        @yield('contenido')
    </main>

    <footer class="pie mt-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-5">
                    <h5>{{ config('app.name') }}</h5>
                    <p class="mb-0" style="max-width: 38ch;">
                        Reserva de escenarios y agenda de eventos del centro de convenciones.
                    </p>
                </div>
                <div class="col-md-3">
                    <h5>Secciones</h5>
                    <ul class="list-unstyled mb-0">
                        <li><a href="{{ route('escenarios.index') }}">Escenarios</a></li>
                        <li><a href="{{ route('eventos.index') }}">Eventos</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Agenda por correo</h5>
                    <p class="mb-2" style="font-size:.88rem;">
                        Te avisamos cuando publiquemos eventos nuevos.
                    </p>
                    <form method="POST" action="{{ route('suscripciones.store') }}" class="d-flex gap-2">
                        @csrf
                        <label for="email-suscripcion" class="visually-hidden">Correo electrónico</label>
                        <input type="email" id="email-suscripcion" name="email" required
                               class="form-control form-control-sm" placeholder="tu@correo.com" maxlength="150">
                        <button type="submit" class="btn btn-principal btn-sm text-nowrap">Avisarme</button>
                    </form>
                    <p class="mt-3 mb-0" style="font-size:.85rem;">
                        {{ config('services.mapa.etiqueta') }}<br>Bogotá, Colombia
                    </p>
                </div>
            </div>

            <hr class="border-secondary my-4">

            <div class="d-flex flex-wrap justify-content-between gap-2 small">
                <span>&copy; {{ date('Y') }} {{ config('app.name') }}</span>
                <span>Proyecto académico modernizado</span>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
            crossorigin="anonymous"></script>
    <script src="{{ asset('js/tema.js') }}" defer></script>

    @stack('scripts')
</body>
</html>

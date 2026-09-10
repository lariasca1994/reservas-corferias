@extends('layout.publico')

@section('titulo', $escenario->nombre)
@section('descripcion', $escenario->resumen)

@section('contenido')
    @php $imagenes = app(\App\Services\AlmacenamientoImagenService::class); @endphp

    <div class="container py-5">

        <nav aria-label="Ruta de navegación" class="mb-4">
            <ol class="breadcrumb small mb-0">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                <li class="breadcrumb-item"><a href="{{ route('escenarios.index') }}">Escenarios</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $escenario->nombre }}</li>
            </ol>
        </nav>

        <div class="row g-5">
            <div class="col-lg-7">

                @if ($escenario->imagenes->isNotEmpty())
                    <div id="galeria" class="carousel slide mb-4" data-bs-ride="carousel">
                        <div class="carousel-inner rounded" style="border-radius: var(--radio);">
                            @foreach ($escenario->imagenes as $i => $imagen)
                                <div class="carousel-item @if($i === 0) active @endif">
                                    <img src="{{ $imagenes->url($imagen->ruta) }}"
                                         class="d-block w-100" style="aspect-ratio:16/9; object-fit:cover;"
                                         alt="{{ $imagen->texto_alternativo }}">
                                </div>
                            @endforeach
                        </div>
                        @if ($escenario->imagenes->count() > 1)
                            <button class="carousel-control-prev" type="button" data-bs-target="#galeria" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Anterior</span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#galeria" data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Siguiente</span>
                            </button>
                        @endif
                    </div>
                @else
                    <img src="{{ $imagenes->url($escenario->imagen_principal) }}"
                         class="w-100 mb-4" style="aspect-ratio:16/9; object-fit:cover; border-radius: var(--radio);"
                         alt="{{ $escenario->nombre }}">
                @endif

                <p class="antetitulo mb-1">Escenario</p>
                <h1 class="mb-3">{{ $escenario->nombre }}</h1>
                <p class="lead text-muted">{{ $escenario->resumen }}</p>

                @if ($escenario->descripcion)
                    <p style="line-height:1.75;">{{ $escenario->descripcion }}</p>
                @endif

                @if ($escenario->caracteristicas->isNotEmpty())
                    <h2 class="h4 mt-5 mb-3">Qué incluye</h2>
                    <div class="row g-3">
                        @foreach ($escenario->caracteristicas as $caracteristica)
                            <div class="col-md-6">
                                <div class="p-3 h-100" style="background: var(--nieve); border-radius: var(--radio-sm);">
                                    <div class="fw-semibold mb-1">{{ $caracteristica->titulo }}</div>
                                    <div class="tarjeta__texto">{{ $caracteristica->descripcion }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="col-lg-5">
                <div class="tarjeta p-4 position-sticky" style="top: 1.5rem;">
                    <div class="dato-destacado">{{ $escenario->precioFormateado() }}</div>
                    <div class="ayuda-campo mb-3">por día · hasta {{ number_format($escenario->capacidad, 0, ',', '.') }} personas</div>

                    <a href="{{ route('reservas.create', $escenario) }}" class="btn btn-principal w-100 mb-4">
                        Solicitar reserva
                    </a>

                    <h2 class="h6 text-uppercase" style="letter-spacing:.08em; color: var(--gris);">
                        Fechas ocupadas
                    </h2>

                    @forelse ($ocupados as $rango)
                        <div class="d-flex justify-content-between border-bottom py-2 small">
                            <span>{{ \Carbon\Carbon::parse($rango['inicio'])->format('d/m/Y') }}</span>
                            <span class="text-muted">al {{ \Carbon\Carbon::parse($rango['fin'])->format('d/m/Y') }}</span>
                        </div>
                    @empty
                        <p class="ayuda-campo mb-0">Sin reservas registradas para los próximos meses.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection

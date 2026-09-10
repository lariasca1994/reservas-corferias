@extends('layout.publico')

@section('titulo', $evento->nombre)
@section('descripcion', $evento->resumen)

@section('contenido')
    @php $imagenes = app(\App\Services\AlmacenamientoImagenService::class); @endphp

    <div class="container py-5">
        <nav aria-label="Ruta de navegación" class="mb-4">
            <ol class="breadcrumb small mb-0">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                <li class="breadcrumb-item"><a href="{{ route('eventos.index') }}">Eventos</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $evento->nombre }}</li>
            </ol>
        </nav>

        <div class="row g-5">
            <div class="col-lg-8">
                <img src="{{ $imagenes->url($evento->imagen) }}" alt="{{ $evento->nombre }}"
                     class="w-100 mb-4" style="aspect-ratio:16/9; object-fit:cover; border-radius: var(--radio);">

                <p class="antetitulo mb-1">Evento</p>
                <h1 class="mb-3">{{ $evento->nombre }}</h1>
                <p class="lead text-muted">{{ $evento->resumen }}</p>

                <div style="line-height:1.8;">
                    @foreach (preg_split('/\r\n|\r|\n/', $evento->descripcion) as $parrafo)
                        @if (trim($parrafo) !== '')
                            <p>{{ $parrafo }}</p>
                        @endif
                    @endforeach
                </div>
            </div>

            <div class="col-lg-4">
                <div class="tarjeta p-4">
                    <h2 class="h6 text-uppercase mb-3" style="letter-spacing:.08em; color: var(--gris);">Detalles</h2>

                    <dl class="mb-0 small">
                        <dt class="text-muted fw-normal">Fechas</dt>
                        <dd>{{ $evento->fecha_inicio->translatedFormat('d \d\e F') }} al
                            {{ $evento->fecha_fin->translatedFormat('d \d\e F \d\e Y') }}</dd>

                        <dt class="text-muted fw-normal">Horario</dt>
                        <dd>{{ $evento->horario }}</dd>

                        @if ($evento->escenario)
                            <dt class="text-muted fw-normal">Escenario</dt>
                            <dd class="mb-0">
                                <a href="{{ route('escenarios.show', $evento->escenario) }}">
                                    {{ $evento->escenario->nombre }}
                                </a>
                            </dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>
    </div>
@endsection

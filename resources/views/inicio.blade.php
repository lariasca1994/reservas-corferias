@extends('layout.publico')

@section('titulo', 'Inicio')
@section('descripcion', 'Reserva escenarios para ferias, congresos y conciertos, y consulta la agenda de eventos.')

@section('contenido')

    <section class="hero">
        <div class="container position-relative">
            <p class="antetitulo" style="color: var(--amarillo);">Centro de convenciones</p>
            <h1 class="mb-3">Tu evento necesita<br>el escenario correcto</h1>
            <p class="lead mb-4">
                Consulta la disponibilidad real de cada espacio y reserva en línea.
                Sin llamadas, sin correos de ida y vuelta.
            </p>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('escenarios.index') }}" class="btn btn-principal">Ver escenarios</a>
                <a href="{{ route('eventos.index') }}" class="btn btn-contorno">Agenda de eventos</a>
            </div>
        </div>
    </section>

    <section class="container py-5">
        <div class="d-flex flex-wrap align-items-end justify-content-between mb-4 gap-2">
            <div>
                <p class="antetitulo mb-1">Espacios disponibles</p>
                <h2 class="mb-0">Escenarios</h2>
            </div>
            <a href="{{ route('escenarios.index') }}" class="text-decoration-none fw-semibold">Ver todos &rarr;</a>
        </div>

        <div class="row g-4">
            @forelse ($escenarios as $escenario)
                <div class="col-sm-6 col-lg-3">
                    <x-tarjeta-escenario :escenario="$escenario" />
                </div>
            @empty
                <div class="col-12">
                    <p class="text-muted">Aún no hay escenarios publicados.</p>
                </div>
            @endforelse
        </div>
    </section>

    <section class="py-5" style="background-color: var(--nieve);">
        <div class="container">
            <p class="antetitulo mb-1">Próximamente</p>
            <h2 class="mb-4">Eventos destacados</h2>

            <div class="row g-4">
                @forelse ($eventosDestacados as $evento)
                    <div class="col-md-4">
                        <article class="tarjeta">
                            <img class="tarjeta__imagen"
                                 src="{{ app(\App\Services\AlmacenamientoImagenService::class)->url($evento->imagen) }}"
                                 alt="{{ $evento->nombre }}" loading="lazy">
                            <div class="tarjeta__cuerpo">
                                <span class="etiqueta etiqueta--neutra mb-2">
                                    {{ $evento->fecha_inicio->translatedFormat('d M') }} —
                                    {{ $evento->fecha_fin->translatedFormat('d M Y') }}
                                </span>
                                <h3 class="tarjeta__titulo">{{ $evento->nombre }}</h3>
                                <p class="tarjeta__texto">{{ $evento->resumen }}</p>
                                <a href="{{ route('eventos.show', $evento) }}" class="fw-semibold text-decoration-none">
                                    Más información &rarr;
                                </a>
                            </div>
                        </article>
                    </div>
                @empty
                    <div class="col-12">
                        <p class="text-muted mb-0">No hay eventos programados por ahora.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <section class="container py-5">
        <div class="row g-4 align-items-center">
            <div class="col-lg-5">
                <p class="antetitulo mb-1">Cómo llegar</p>
                <h2 class="mb-3">Estamos en el corazón de Bogotá</h2>
                <p class="text-muted mb-0">
                    {{ config('services.mapa.etiqueta') }}. Acceso directo desde las principales
                    rutas de transporte público de la ciudad.
                </p>
            </div>
            <div class="col-lg-7">
                <x-mapa />
            </div>
        </div>
    </section>

@endsection

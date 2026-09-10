@extends('layout.publico')

@section('titulo', 'Eventos')
@section('descripcion', 'Agenda de eventos programados en el centro de convenciones.')

@section('contenido')
    @php $imagenes = app(\App\Services\AlmacenamientoImagenService::class); @endphp

    <div class="container py-5">
        <p class="antetitulo mb-1">Agenda</p>
        <h1 class="mb-5">Eventos programados</h1>

        <div class="row g-4">
            @forelse ($eventos as $evento)
                <div class="col-sm-6 col-lg-4">
                    <article class="tarjeta">
                        <img class="tarjeta__imagen" src="{{ $imagenes->url($evento->imagen) }}"
                             alt="{{ $evento->nombre }}" loading="lazy">
                        <div class="tarjeta__cuerpo">
                            <span class="etiqueta etiqueta--neutra mb-2">
                                {{ $evento->fecha_inicio->translatedFormat('d M') }} —
                                {{ $evento->fecha_fin->translatedFormat('d M Y') }}
                            </span>
                            <h2 class="tarjeta__titulo">{{ $evento->nombre }}</h2>
                            <p class="tarjeta__texto">{{ $evento->resumen }}</p>
                            @if ($evento->escenario)
                                <div class="ayuda-campo mb-2">En {{ $evento->escenario->nombre }}</div>
                            @endif
                            <a href="{{ route('eventos.show', $evento) }}" class="fw-semibold text-decoration-none">
                                Más información &rarr;
                            </a>
                        </div>
                    </article>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-light border">No hay eventos programados por ahora.</div>
                </div>
            @endforelse
        </div>

        <div class="mt-5">{{ $eventos->links() }}</div>
    </div>
@endsection

@extends('layout.publico')

@section('titulo', 'Escenarios')
@section('descripcion', 'Conoce los escenarios disponibles, su capacidad y su tarifa por día.')

@section('contenido')
    <div class="container py-5">
        <p class="antetitulo mb-1">Catálogo</p>
        <h1 class="mb-2">Escenarios</h1>
        <p class="text-muted mb-5" style="max-width: 60ch;">
            Cada espacio tiene su propia configuración, capacidad y tarifa.
            Consulta el detalle para ver las fechas ya ocupadas antes de reservar.
        </p>

        <div class="row g-4">
            @forelse ($escenarios as $escenario)
                <div class="col-sm-6 col-lg-4">
                    <x-tarjeta-escenario :escenario="$escenario" />
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-light border">No hay escenarios publicados en este momento.</div>
                </div>
            @endforelse
        </div>
    </div>
@endsection

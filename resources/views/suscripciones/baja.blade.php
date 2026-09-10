@extends('layout.publico')

@section('titulo', 'Suscripción cancelada')

@section('contenido')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-6">
                <div class="tarjeta p-4 p-md-5 text-center">
                    <p class="antetitulo mb-2">Lista de distribución</p>
                    <h1 class="h3 mb-3">Suscripción cancelada</h1>

                    <p class="text-muted mb-4">
                        @if ($email)
                            Ya no enviaremos avisos de eventos a <strong>{{ $email }}</strong>.
                        @else
                            Si esa dirección estaba en nuestra lista, ya no recibirá más avisos.
                        @endif
                    </p>

                    <a href="{{ route('inicio') }}" class="btn btn-principal">Volver al inicio</a>
                </div>
            </div>
        </div>
    </div>
@endsection

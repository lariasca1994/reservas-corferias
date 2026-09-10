@extends('layout.publico')

@section('titulo', 'Confirma tu correo')

@section('contenido')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-6">
                <div class="tarjeta p-4 p-md-5">
                    <p class="antetitulo mb-2">Un paso más</p>
                    <h1 class="h3 mb-3">Confirma tu correo</h1>

                    <p class="text-muted">
                        Te enviamos un enlace a <strong>{{ auth()->user()->email }}</strong>.
                        Ábrelo para activar tu cuenta.
                    </p>

                    <p class="ayuda-campo mb-4">
                        Pedimos esta confirmación porque tu historial puede incluir
                        reservas hechas antes de registrarte con esta misma dirección.
                        Sin verificarla, cualquiera podría reclamarlas.
                    </p>

                    <form method="POST" action="{{ route('verification.send') }}">
                        @csrf
                        <button type="submit" class="btn btn-principal w-100">
                            Reenviar el enlace
                        </button>
                    </form>

                    <form method="POST" action="{{ route('logout') }}" class="mt-3 text-center">
                        @csrf
                        <button type="submit" class="btn btn-link text-decoration-none">
                            Cerrar sesión
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@extends('layout.publico')

@section('titulo', 'Recuperar contraseña')

@section('contenido')
    <div class="container py-5">
        <div class="tarjeta p-4 p-md-5 mx-auto" style="max-width: 480px;">
            <p class="antetitulo mb-1">Acceso</p>
            <h1 class="h3 mb-3">¿Olvidaste tu contraseña?</h1>
            <p class="text-muted mb-4">Escribe el correo de tu cuenta y te enviaremos un enlace para crear una nueva. El enlace se puede usar una sola vez.</p>

            <form method="POST" action="{{ route('password.email') }}" novalidate>
                @csrf
                <div class="mb-4">
                    <label for="email" class="form-label">Correo electrónico</label>
                    <input type="email" id="email" name="email"
                           class="form-control @error('email') is-invalid @enderror"
                           value="{{ old('email') }}" required autofocus autocomplete="username">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="btn btn-principal w-100">Enviar enlace</button>
            </form>

            <p class="text-center mt-3 mb-0"><a href="{{ route('login') }}">Volver a ingresar</a></p>
        </div>
    </div>
@endsection

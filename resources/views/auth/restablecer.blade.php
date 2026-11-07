@extends('layout.publico')

@section('titulo', 'Nueva contraseña')

@section('contenido')
    <div class="container py-5">
        <div class="tarjeta p-4 p-md-5 mx-auto" style="max-width: 480px;">
            <p class="antetitulo mb-1">Acceso</p>
            <h1 class="h3 mb-4">Crea una nueva contraseña</h1>

            @error('enlace')
                <div class="alert alert-danger" role="alert">{{ $message }}</div>
            @enderror

            @if (! $valido)
                @unless ($errors->has('enlace'))
                    <div class="alert alert-danger" role="alert">El enlace no es válido o ya venció. Pide uno nuevo.</div>
                @endunless
                <a href="{{ route('password.request') }}" class="btn btn-principal w-100">Pedir un enlace nuevo</a>
            @else
                {{-- El correo de la cuenta viene del enlace y queda en el servidor: aqui no se pide. --}}
                <form method="POST" action="{{ route('password.update') }}" novalidate>
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <div class="mb-3">
                        <label for="password" class="form-label">Nueva contraseña</label>
                        <input type="password" id="password" name="password"
                               class="form-control @error('password') is-invalid @enderror"
                               required autofocus autocomplete="new-password">
                        <div class="form-text">Mínimo 10 caracteres, con letras y números.</div>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label">Confirma la nueva contraseña</label>
                        <input type="password" id="password_confirmation" name="password_confirmation"
                               class="form-control" required autocomplete="new-password">
                    </div>

                    <button type="submit" class="btn btn-principal w-100">Guardar contraseña</button>
                    <p class="form-text text-center mt-3 mb-0">Al guardarla se cierran las sesiones abiertas. La verificación en dos pasos sigue activa.</p>
                </form>
            @endif

            <p class="text-center mt-3 mb-0"><a href="{{ route('login') }}">Volver a ingresar</a></p>
        </div>
    </div>
@endsection

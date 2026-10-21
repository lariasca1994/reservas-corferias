@extends('layout.publico')

@section('titulo', 'Cambiar contraseña')

@section('contenido')
    <div class="container py-5">
        <div class="tarjeta p-4 p-md-5 mx-auto" style="max-width: 480px;">
            <p class="antetitulo mb-1">Mi cuenta</p>
            <h1 class="h3 mb-4">Cambiar contraseña</h1>

            <form method="POST" action="{{ route('cuenta.contrasena.actualizar') }}" novalidate>
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="actual" class="form-label">Contraseña actual</label>
                    <input type="password" id="actual" name="actual"
                           class="form-control @error('actual') is-invalid @enderror"
                           required autocomplete="current-password">
                    @error('actual')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Nueva contraseña</label>
                    <input type="password" id="password" name="password"
                           class="form-control @error('password') is-invalid @enderror"
                           required autocomplete="new-password">
                    <div class="form-text">Mínimo 10 caracteres, con letras y números.</div>
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-4">
                    <label for="password_confirmation" class="form-label">Confirma la nueva contraseña</label>
                    <input type="password" id="password_confirmation" name="password_confirmation"
                           class="form-control" required autocomplete="new-password">
                </div>

                <button type="submit" class="btn btn-principal w-100">Guardar contraseña</button>
            </form>
        </div>
    </div>
@endsection

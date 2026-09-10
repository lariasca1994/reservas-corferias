@extends('layout.publico')

@section('titulo', 'Ingresar')

@section('contenido')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="tarjeta p-4 p-md-5">
                    <p class="antetitulo mb-1">Acceso</p>
                    <h1 class="h3 mb-4">Ingresar al panel</h1>

                    <form method="POST" action="{{ route('login') }}" novalidate>
                        @csrf

                        <div class="mb-3">
                            <label for="email" class="form-label">Correo electrónico</label>
                            <input type="email" id="email" name="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email') }}" required autofocus autocomplete="username">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña</label>
                            <input type="password" id="password" name="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   required autocomplete="current-password">
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="recordarme" name="recordarme" value="1">
                            <label class="form-check-label" for="recordarme">Mantener la sesión abierta</label>
                        </div>

                        <button type="submit" class="btn btn-principal w-100">Ingresar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

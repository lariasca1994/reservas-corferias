@extends('layout.publico')

@section('titulo', 'Crear cuenta')

@section('contenido')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-6">
                <div class="tarjeta p-4 p-md-5">
                    <p class="antetitulo mb-1">Nueva cuenta</p>
                    <h1 class="h3 mb-2">Crea tu cuenta</h1>
                    <p class="text-muted mb-4">
                        Con una cuenta puedes consultar el historial de tus reservas
                        y cancelarlas sin llamar a nadie.
                    </p>

                    <form method="POST" action="{{ route('registro') }}" novalidate>
                        @csrf

                        <div class="mb-3">
                            <label for="name" class="form-label">Nombre completo</label>
                            <input type="text" id="name" name="name" maxlength="120" required autofocus
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name') }}" autocomplete="name">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Correo electrónico</label>
                            <input type="email" id="email" name="email" maxlength="150" required
                                   class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email') }}" autocomplete="email">
                            <div class="ayuda-campo">
                                Si ya reservaste antes con este correo, tus reservas quedarán asociadas a la cuenta.
                            </div>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="telefono" class="form-label">Teléfono <span class="text-muted fw-normal">(opcional)</span></label>
                            <input type="tel" id="telefono" name="telefono" maxlength="40"
                                   class="form-control @error('telefono') is-invalid @enderror"
                                   value="{{ old('telefono') }}" autocomplete="tel">
                            @error('telefono')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="password" class="form-label">Contraseña</label>
                                <input type="password" id="password" name="password" required
                                       class="form-control @error('password') is-invalid @enderror"
                                       autocomplete="new-password">
                                <div class="ayuda-campo">Mínimo 10 caracteres, con letras y números.</div>
                                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="password_confirmation" class="form-label">Confirmar</label>
                                <input type="password" id="password_confirmation" name="password_confirmation"
                                       required class="form-control" autocomplete="new-password">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-principal w-100">Crear cuenta</button>
                    </form>

                    <p class="text-center text-muted small mt-4 mb-0">
                        ¿Ya tienes cuenta? <a href="{{ route('login') }}">Ingresa aquí</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection

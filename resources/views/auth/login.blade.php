@extends('layout.publico')

@section('titulo', 'Ingresar')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/acceso.css') }}">
@endpush

@section('contenido')
    <div class="container py-5">
        <div class="acceso">
            {{-- Panel de marca: decorativo (aria-hidden). Una franja libre de la agenda
                 se busca, se reserva y se confirma, en un ciclo de 6 s (ver css/acceso.css). --}}
            <aside class="acceso__marca" aria-hidden="true">
                <div>
                    <p class="acceso__etiqueta">Centro de convenciones</p>
                    <p class="acceso__titular">Tu escenario, <span>reservado en minutos.</span></p>
                </div>

                <div class="agenda">
                    <div class="agenda__fila">
                        <span></span>
                        <span class="agenda__dia">Lun</span><span class="agenda__dia">Mar</span><span class="agenda__dia">Mié</span><span class="agenda__dia">Jue</span><span class="agenda__dia">Vie</span>
                    </div>
                    <div class="agenda__fila">
                        <span class="agenda__escenario">Pabellón 1</span>
                        <span class="franja franja--ocupada"></span><span class="franja franja--busca"></span><span class="franja franja--ocupada"></span><span class="franja"></span><span class="franja franja--ocupada"></span>
                    </div>
                    <div class="agenda__fila">
                        <span class="agenda__escenario">Gran Salón</span>
                        <span class="franja"></span><span class="franja franja--ocupada"></span><span class="franja franja--busca franja--busca-2"></span><span class="franja franja--objetivo"></span><span class="franja"></span>
                    </div>
                    <div class="agenda__fila">
                        <span class="agenda__escenario">Auditorio</span>
                        <span class="franja franja--ocupada"></span><span class="franja"></span><span class="franja"></span><span class="franja franja--ocupada"></span><span class="franja franja--ocupada"></span>
                    </div>
                    <div class="agenda__aviso">Reserva registrada · Gran Salón</div>
                </div>

                <p class="acceso__nota">Consulta la disponibilidad de cada escenario y solicita tu reserva.</p>
            </aside>

            <div class="tarjeta acceso__form p-4 p-md-5">
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

                <p class="text-center mt-3 mb-0">
                    <a href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a>
                </p>
            </div>
        </div>
    </div>
@endsection

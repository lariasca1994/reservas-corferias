@extends('layout.publico')

@section('titulo', 'Verificación en dos pasos')

@section('contenido')
    <div class="container py-5">
        <div class="tarjeta p-4 p-md-5 mx-auto" style="max-width: 480px;">
            <p class="antetitulo mb-1">Un paso más</p>
            <h1 class="h3 mb-3">Verificación en dos pasos</h1>

            @if (session('exito'))
                <div class="alert alert-success" role="status">{{ session('exito') }}</div>
            @endif
            @error('codigo')
                <div class="alert alert-danger" role="alert">{{ $message }}</div>
            @enderror

            @if ($modo === 'elegir')
                <p class="text-muted mb-4">Además de la contraseña, cada ingreso pedirá un código. Elige cómo recibirlo; después puedes agregar el otro en Seguridad.</p>
                <form method="POST" action="{{ route('verificacion.configurar') }}">
                    @csrf
                    <button type="submit" name="metodo" value="totp" class="btn btn-principal w-100">App autenticadora (recomendado)</button>
                    <p class="form-text mb-3">Google Authenticator, Microsoft Authenticator u otra: funciona sin internet.</p>
                    <button type="submit" name="metodo" value="correo" class="btn btn-principal w-100">Código por correo</button>
                    <p class="form-text">Te enviamos un código de 6 dígitos cada vez que ingreses.</p>
                </form>
            @else
                @php
                    $accion = match ($modo) {
                        'activar', 'reemplazar' => route('verificacion.activar'),
                        'recuperar'             => route('verificacion.recuperar'),
                        default                 => route('verificacion.verificar'),
                    };
                @endphp
                @if ($modo === 'reemplazar')
                    <p class="text-muted mb-3">Configura tu app autenticadora nueva: la anterior deja de servir.</p>
                @endif
                @if (in_array($modo, ['activar', 'reemplazar'], true) && $metodo === 'totp' && $app)
                    <p class="mb-2">1. Escanea este código con tu app autenticadora.</p>
                    {{-- SVG generado por el servidor (bacon/bacon-qr-code), no por el usuario. --}}
                    <div class="mfa-qr mx-auto mb-2" aria-label="Código QR para la app autenticadora">{!! $app['qr'] !!}</div>
                    <p class="small">¿No puedes escanear? Escribe esta clave: <code class="mfa-clave">{{ $app['secreto'] }}</code></p>
                    <p class="mb-3">2. Escribe el código de 6 dígitos que muestra la app.</p>
                @elseif ($modo === 'recuperar')
                    <p class="text-muted mb-4">Te enviamos un código de recuperación de 6 dígitos a {{ $email }}. Vence en {{ $minutosRecuperacion }} minutos. Después configurarás tu app autenticadora nueva.</p>
                @elseif ($metodo === 'totp')
                    <p class="text-muted mb-4">Abre tu app autenticadora y escribe el código de 6 dígitos.</p>
                @else
                    <p class="text-muted mb-4">Te enviamos un código de 6 dígitos a {{ $email }}. Vence en 10 minutos.</p>
                @endif

                <form method="POST" action="{{ $accion }}" novalidate>
                    @csrf
                    @if ($modo !== 'recuperar')
                        <input type="hidden" name="metodo" value="{{ $metodo }}">
                    @endif
                    <div class="mb-4">
                        <label for="codigo" class="form-label">Código de 6 dígitos</label>
                        <input type="text" id="codigo" name="codigo" class="form-control" required autofocus
                               inputmode="numeric" pattern="[0-9 ]{6,7}" maxlength="7" autocomplete="one-time-code">
                    </div>
                    <button type="submit" class="btn btn-principal w-100">{{ in_array($modo, ['activar', 'reemplazar'], true) ? 'Activar' : 'Verificar' }}</button>
                </form>

                <div class="text-center mt-3 d-flex flex-wrap justify-content-center gap-3">
                    @if ($modo === 'verificar' && in_array('correo', [$metodo, $otro], true))
                        <form method="POST" action="{{ route('verificacion.correo') }}">
                            @csrf
                            <button type="submit" class="btn btn-link p-0">{{ $metodo === 'correo' ? 'Enviar otro código' : 'Usar un código por correo' }}</button>
                        </form>
                    @endif
                    @if ($modo === 'verificar' && $otro === 'totp')
                        <a href="{{ route('verificacion', ['metodo' => 'totp']) }}">Usar la app autenticadora</a>
                    @endif
                    @if ($modo === 'activar')
                        <a href="{{ route('verificacion') }}">Elegir otro método</a>
                    @endif
                    @if ($modo === 'recuperar')
                        <form method="POST" action="{{ route('verificacion.perdi') }}">
                            @csrf
                            <button type="submit" class="btn btn-link p-0">Enviar otro código</button>
                        </form>
                        <a href="{{ route('verificacion', ['metodo' => 'totp']) }}">Ya tengo mi autenticador</a>
                    @endif
                </div>

                @if ($modo === 'verificar')
                    {{-- Siempre visible: llega al correo de la cuenta aunque el metodo correo no este activo. --}}
                    <form method="POST" action="{{ route('verificacion.perdi') }}" class="text-center mt-3">
                        @csrf
                        <button type="submit" class="btn btn-link p-0">Perdí mi autenticador</button>
                    </form>
                @endif
            @endif

            <p class="text-center mt-3 mb-0"><a href="{{ route('login') }}">Volver a ingresar</a></p>
        </div>
    </div>
@endsection

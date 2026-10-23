@extends(auth()->user()->puedeGestionar() ? 'layout.panel' : 'layout.publico')

@section('titulo', 'Seguridad')

@section('contenido')
    <div class="container py-5">
        <div class="tarjeta p-4 p-md-5 mx-auto" style="max-width: 560px;">
            <p class="antetitulo mb-1">Mi cuenta</p>
            <h1 class="h3 mb-2">Verificación en dos pasos</h1>
            <p class="text-muted mb-4">Cada ingreso pide, además de la contraseña, un código de uno de tus métodos.</p>

            @if (session('exito'))
                <div class="alert alert-success" role="status">{{ session('exito') }}</div>
            @endif
            @error('codigo')
                <div class="alert alert-danger" role="alert">{{ $message }}</div>
            @enderror

            <ul class="list-unstyled mfa-metodos">
                @foreach ($metodos as $clave => $nombre)
                    <li>
                        <div>
                            <strong>{{ $nombre }}</strong><br>
                            <small class="text-muted">
                                @if (in_array($clave, $activos, true))
                                    Activa{{ $clave === $preferido ? ' · se pide por defecto' : '' }}
                                @else
                                    Sin configurar
                                @endif
                            </small>
                        </div>
                        @if (! in_array($clave, $activos, true))
                            <form method="POST" action="{{ route('cuenta.seguridad.agregar') }}">
                                @csrf
                                <button type="submit" name="metodo" value="{{ $clave }}" class="btn btn-principal btn-sm">Agregar</button>
                            </form>
                        @elseif ($clave !== $preferido)
                            <form method="POST" action="{{ route('cuenta.seguridad.preferido') }}">
                                @csrf
                                <button type="submit" name="metodo" value="{{ $clave }}" class="btn btn-outline-secondary btn-sm">Pedir este por defecto</button>
                            </form>
                        @endif
                    </li>
                @endforeach
            </ul>

            @if ($agregando)
                <div class="border-top pt-4 mt-2">
                    <h2 class="h5 mb-3">Agregar: {{ $metodos[$agregando] }}</h2>
                    @if ($agregando === 'totp' && $app)
                        <p class="mb-2">1. Escanea este código con tu app autenticadora.</p>
                        {{-- SVG generado por el servidor (bacon/bacon-qr-code), no por el usuario. --}}
                        <div class="mfa-qr mb-2">{!! $app['qr'] !!}</div>
                        <p class="small">¿No puedes escanear? Escribe esta clave: <code class="mfa-clave">{{ $app['secreto'] }}</code></p>
                        <p class="mb-3">2. Escribe el código de 6 dígitos que muestra la app.</p>
                    @else
                        <p class="mb-3">Te enviamos un código de 6 dígitos a <strong>{{ $email }}</strong>. Vence en 10 minutos.</p>
                    @endif
                    <form method="POST" action="{{ route('cuenta.seguridad.activar') }}" novalidate>
                        @csrf
                        <input type="hidden" name="metodo" value="{{ $agregando }}">
                        <div class="mb-3">
                            <label for="codigo" class="form-label">Código de 6 dígitos</label>
                            <input type="text" id="codigo" name="codigo" class="form-control" required autofocus
                                   inputmode="numeric" pattern="[0-9 ]{6,7}" maxlength="7" autocomplete="one-time-code">
                        </div>
                        <button type="submit" class="btn btn-principal">Activar</button>
                    </form>
                </div>
            @endif
        </div>
    </div>
@endsection

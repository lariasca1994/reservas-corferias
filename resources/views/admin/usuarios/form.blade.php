@extends('layout.panel')

@section('titulo', $usuario ? 'Editar cuenta' : 'Nueva cuenta')

@section('contenido')
    @php
        $accion  = $usuario ? route('admin.usuarios.update', $usuario) : route('admin.usuarios.store');
        $esYoMismo = $usuario?->is(auth()->user()) ?? false;
    @endphp

    <a href="{{ route('admin.usuarios.index') }}" class="small text-decoration-none">&larr; Volver</a>
    <h1 class="h3 mt-2 mb-4">{{ $usuario ? 'Editar cuenta' : 'Nueva cuenta' }}</h1>

    <div class="row">
        <div class="col-lg-7">
            <form method="POST" action="{{ $accion }}" novalidate>
                @csrf
                @if ($usuario) @method('PUT') @endif

                <div class="tarjeta p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="name" class="form-label">Nombre</label>
                            <input type="text" id="name" name="name" maxlength="120" required
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $usuario?->name) }}">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-7">
                            <label for="email" class="form-label">Correo electrónico</label>
                            <input type="email" id="email" name="email" maxlength="150" required
                                   class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email', $usuario?->email) }}">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-5">
                            <label for="telefono" class="form-label">Teléfono</label>
                            <input type="tel" id="telefono" name="telefono" maxlength="40"
                                   class="form-control @error('telefono') is-invalid @enderror"
                                   value="{{ old('telefono', $usuario?->telefono) }}">
                            @error('telefono')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label for="rol" class="form-label">Rol</label>
                            <select id="rol" name="rol" class="form-select @error('rol') is-invalid @enderror"
                                    @disabled($esYoMismo)>
                                <option value="{{ \App\Models\User::ROL_CLIENTE }}"
                                    @selected(old('rol', $usuario?->rol) === \App\Models\User::ROL_CLIENTE)>Cliente</option>
                                <option value="{{ \App\Models\User::ROL_OPERADOR }}"
                                    @selected(old('rol', $usuario?->rol) === \App\Models\User::ROL_OPERADOR)>Operador</option>
                                <option value="{{ \App\Models\User::ROL_ADMINISTRADOR }}"
                                    @selected(old('rol', $usuario?->rol) === \App\Models\User::ROL_ADMINISTRADOR)>Administrador</option>
                            </select>
                            @if ($esYoMismo)
                                <input type="hidden" name="rol" value="{{ $usuario->rol }}">
                                <div class="ayuda-campo">No puedes cambiar tu propio rol.</div>
                            @endif
                            @error('rol')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="activo" name="activo" value="1"
                                       @checked(old('activo', $usuario?->activo ?? true)) @disabled($esYoMismo)>
                                <label class="form-check-label" for="activo">Cuenta activa</label>
                            </div>
                        </div>

                        <div class="col-12"><hr class="my-2"></div>

                        <div class="col-md-6">
                            <label for="password" class="form-label">Contraseña</label>
                            <input type="password" id="password" name="password" autocomplete="new-password"
                                   class="form-control @error('password') is-invalid @enderror">
                            <div class="ayuda-campo">
                                @if ($usuario) Déjala vacía para conservar la actual. @else Mínimo 10 caracteres, con letras y números. @endif
                            </div>
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label for="password_confirmation" class="form-label">Confirmar contraseña</label>
                            <input type="password" id="password_confirmation" name="password_confirmation"
                                   autocomplete="new-password" class="form-control">
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-principal">
                                {{ $usuario ? 'Guardar cambios' : 'Crear cuenta' }}
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@extends('layout.panel')

@section('titulo', $escenario ? 'Editar escenario' : 'Nuevo escenario')

@section('contenido')
    @php
        $accion    = $escenario ? route('admin.escenarios.update', $escenario) : route('admin.escenarios.store');
        $actuales  = old('caracteristicas', $escenario?->caracteristicas->map(fn ($c) => [
            'titulo' => $c->titulo, 'descripcion' => $c->descripcion,
        ])->all() ?? []);
    @endphp

    <a href="{{ route('admin.escenarios.index') }}" class="small text-decoration-none">&larr; Volver</a>
    <h1 class="h3 mt-2 mb-4">{{ $escenario ? 'Editar escenario' : 'Nuevo escenario' }}</h1>

    <form method="POST" action="{{ $accion }}" enctype="multipart/form-data" novalidate>
        @csrf
        @if ($escenario) @method('PUT') @endif

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="tarjeta p-4">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="nombre" class="form-label">Nombre</label>
                            <input type="text" id="nombre" name="nombre" maxlength="120" required
                                   class="form-control @error('nombre') is-invalid @enderror"
                                   value="{{ old('nombre', $escenario?->nombre) }}">
                            @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label for="slug" class="form-label">Identificador</label>
                            <input type="text" id="slug" name="slug" maxlength="60"
                                   class="form-control @error('slug') is-invalid @enderror"
                                   value="{{ old('slug', $escenario?->slug) }}">
                            <div class="ayuda-campo">Se usa en la URL.</div>
                            @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label for="resumen" class="form-label">Resumen</label>
                            <input type="text" id="resumen" name="resumen" maxlength="255" required
                                   class="form-control @error('resumen') is-invalid @enderror"
                                   value="{{ old('resumen', $escenario?->resumen) }}">
                            @error('resumen')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea id="descripcion" name="descripcion" rows="5" maxlength="4000"
                                      class="form-control @error('descripcion') is-invalid @enderror">{{ old('descripcion', $escenario?->descripcion) }}</textarea>
                            @error('descripcion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="tarjeta p-4 mt-4">
                    <h2 class="h6 mb-3">Características</h2>
                    <p class="ayuda-campo">Hasta diez. Las que dejes vacías no se guardan.</p>

                    @for ($i = 0; $i < 6; $i++)
                        <div class="row g-2 mb-2">
                            <div class="col-md-4">
                                <input type="text" name="caracteristicas[{{ $i }}][titulo]" maxlength="120"
                                       class="form-control" placeholder="Título"
                                       value="{{ $actuales[$i]['titulo'] ?? '' }}">
                            </div>
                            <div class="col-md-8">
                                <input type="text" name="caracteristicas[{{ $i }}][descripcion]" maxlength="400"
                                       class="form-control" placeholder="Descripción"
                                       value="{{ $actuales[$i]['descripcion'] ?? '' }}">
                            </div>
                        </div>
                    @endfor
                </div>
            </div>

            <div class="col-lg-4">
                <div class="tarjeta p-4">
                    <div class="mb-3">
                        <label for="precio_dia" class="form-label">Precio por día (COP)</label>
                        <input type="number" id="precio_dia" name="precio_dia" min="0" step="1000" required
                               class="form-control @error('precio_dia') is-invalid @enderror"
                               value="{{ old('precio_dia', $escenario?->precio_dia) }}">
                        @error('precio_dia')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="capacidad" class="form-label">Capacidad</label>
                        <input type="number" id="capacidad" name="capacidad" min="1" required
                               class="form-control @error('capacidad') is-invalid @enderror"
                               value="{{ old('capacidad', $escenario?->capacidad) }}">
                        @error('capacidad')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="imagen" class="form-label">Imagen principal</label>
                        <input type="file" id="imagen" name="imagen" accept="image/jpeg,image/png,image/webp"
                               class="form-control @error('imagen') is-invalid @enderror">
                        <div class="ayuda-campo">JPG, PNG o WEBP · mínimo 600×400 · máximo 3 MB</div>
                        @error('imagen')<div class="invalid-feedback">{{ $message }}</div>@enderror

                        @if ($escenario?->imagen_principal)
                            <img class="mt-3 w-100" style="aspect-ratio:16/10; object-fit:cover; border-radius: var(--radio-sm);"
                                 src="{{ app(\App\Services\AlmacenamientoImagenService::class)->url($escenario->imagen_principal) }}"
                                 alt="Imagen actual de {{ $escenario->nombre }}">
                        @endif
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="activo" name="activo" value="1"
                               @checked(old('activo', $escenario?->activo ?? true))>
                        <label class="form-check-label" for="activo">Visible en el sitio público</label>
                    </div>

                    <button type="submit" class="btn btn-principal w-100">
                        {{ $escenario ? 'Guardar cambios' : 'Crear escenario' }}
                    </button>
                </div>
            </div>
        </div>
    </form>
@endsection

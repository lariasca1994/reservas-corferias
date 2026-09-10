@extends('layout.panel')

@section('titulo', $evento ? 'Editar evento' : 'Nuevo evento')

@section('contenido')
    @php $accion = $evento ? route('admin.eventos.update', $evento) : route('admin.eventos.store'); @endphp

    <a href="{{ route('admin.eventos.index') }}" class="small text-decoration-none">&larr; Volver</a>
    <h1 class="h3 mt-2 mb-4">{{ $evento ? 'Editar evento' : 'Nuevo evento' }}</h1>

    <form method="POST" action="{{ $accion }}" enctype="multipart/form-data" novalidate>
        @csrf
        @if ($evento) @method('PUT') @endif

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="tarjeta p-4">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="nombre" class="form-label">Nombre</label>
                            <input type="text" id="nombre" name="nombre" maxlength="150" required
                                   class="form-control @error('nombre') is-invalid @enderror"
                                   value="{{ old('nombre', $evento?->nombre) }}">
                            @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label for="slug" class="form-label">Identificador</label>
                            <input type="text" id="slug" name="slug" maxlength="80"
                                   class="form-control @error('slug') is-invalid @enderror"
                                   value="{{ old('slug', $evento?->slug) }}">
                            @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label for="resumen" class="form-label">Resumen</label>
                            <input type="text" id="resumen" name="resumen" maxlength="255" required
                                   class="form-control @error('resumen') is-invalid @enderror"
                                   value="{{ old('resumen', $evento?->resumen) }}">
                            @error('resumen')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea id="descripcion" name="descripcion" rows="8" maxlength="8000" required
                                      class="form-control @error('descripcion') is-invalid @enderror">{{ old('descripcion', $evento?->descripcion) }}</textarea>
                            <div class="ayuda-campo">Separa los párrafos con saltos de línea.</div>
                            @error('descripcion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="tarjeta p-4">
                    <div class="row g-3">
                        <div class="col-6">
                            <label for="fecha_inicio" class="form-label">Inicio</label>
                            <input type="date" id="fecha_inicio" name="fecha_inicio" required
                                   class="form-control @error('fecha_inicio') is-invalid @enderror"
                                   value="{{ old('fecha_inicio', $evento?->fecha_inicio?->toDateString()) }}">
                            @error('fecha_inicio')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-6">
                            <label for="fecha_fin" class="form-label">Fin</label>
                            <input type="date" id="fecha_fin" name="fecha_fin" required
                                   class="form-control @error('fecha_fin') is-invalid @enderror"
                                   value="{{ old('fecha_fin', $evento?->fecha_fin?->toDateString()) }}">
                            @error('fecha_fin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label for="horario" class="form-label">Horario</label>
                            <input type="text" id="horario" name="horario" maxlength="80" required
                                   class="form-control @error('horario') is-invalid @enderror"
                                   placeholder="9:00 a. m. a 6:00 p. m."
                                   value="{{ old('horario', $evento?->horario) }}">
                            @error('horario')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label for="escenario_id" class="form-label">Escenario</label>
                            <select id="escenario_id" name="escenario_id" class="form-select">
                                <option value="">Sin asignar</option>
                                @foreach ($escenarios as $opcion)
                                    <option value="{{ $opcion->id }}"
                                        @selected(old('escenario_id', $evento?->escenario_id) == $opcion->id)>
                                        {{ $opcion->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12">
                            <label for="imagen" class="form-label">Imagen</label>
                            <input type="file" id="imagen" name="imagen" accept="image/jpeg,image/png,image/webp"
                                   class="form-control @error('imagen') is-invalid @enderror">
                            <div class="ayuda-campo">JPG, PNG o WEBP · máximo 3 MB</div>
                            @error('imagen')<div class="invalid-feedback">{{ $message }}</div>@enderror

                            @if ($evento?->imagen)
                                <img class="mt-3 w-100" style="aspect-ratio:16/10; object-fit:cover; border-radius: var(--radio-sm);"
                                     src="{{ app(\App\Services\AlmacenamientoImagenService::class)->url($evento->imagen) }}"
                                     alt="Imagen actual del evento">
                            @endif
                        </div>

                        <div class="col-12 form-check ms-2">
                            <input class="form-check-input" type="checkbox" id="destacado" name="destacado" value="1"
                                   @checked(old('destacado', $evento?->destacado))>
                            <label class="form-check-label" for="destacado">Mostrar en la página de inicio</label>
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-principal w-100">
                                {{ $evento ? 'Guardar cambios' : 'Crear evento' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

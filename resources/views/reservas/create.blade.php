@extends('layout.publico')

@section('titulo', 'Reservar '.$escenario->nombre)

@section('contenido')
    <div class="container py-5">
        <div class="row g-5 justify-content-center">
            <div class="col-lg-7">
                <p class="antetitulo mb-1">Solicitud de reserva</p>
                <h1 class="mb-4">{{ $escenario->nombre }}</h1>

                <form method="POST" action="{{ route('reservas.store', $escenario) }}" novalidate>
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="fecha_inicio" class="form-label">Fecha de inicio</label>
                            <input type="date" id="fecha_inicio" name="fecha_inicio"
                                   class="form-control @error('fecha_inicio') is-invalid @enderror"
                                   value="{{ old('fecha_inicio') }}"
                                   min="{{ now()->toDateString() }}" required>
                            @error('fecha_inicio')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label for="fecha_fin" class="form-label">Fecha final</label>
                            <input type="date" id="fecha_fin" name="fecha_fin"
                                   class="form-control @error('fecha_fin') is-invalid @enderror"
                                   value="{{ old('fecha_fin') }}"
                                   min="{{ now()->toDateString() }}" required>
                            @error('fecha_fin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <div id="aviso-disponibilidad" class="alert alert-warning d-none mb-0" role="status"></div>
                            <div id="resumen-costo" class="p-3 d-none" style="background: var(--nieve); border-radius: var(--radio-sm);">
                                <span id="resumen-dias" class="fw-semibold"></span>
                                <span class="float-end dato-destacado" id="resumen-total"></span>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="nombre_contacto" class="form-label">Nombre de quien reserva</label>
                            <input type="text" id="nombre_contacto" name="nombre_contacto"
                                   class="form-control @error('nombre_contacto') is-invalid @enderror"
                                   value="{{ old('nombre_contacto', auth()->user()?->name) }}" maxlength="120" required>
                            @error('nombre_contacto')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-7">
                            <label for="email_contacto" class="form-label">Correo electrónico</label>
                            <input type="email" id="email_contacto" name="email_contacto"
                                   class="form-control @error('email_contacto') is-invalid @enderror"
                                   value="{{ old('email_contacto', auth()->user()?->email) }}" maxlength="150" required>
                            <div class="ayuda-campo">Aquí enviaremos el comprobante con el código QR.</div>
                            @error('email_contacto')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-5">
                            <label for="telefono_contacto" class="form-label">Teléfono</label>
                            <input type="tel" id="telefono_contacto" name="telefono_contacto"
                                   class="form-control @error('telefono_contacto') is-invalid @enderror"
                                   value="{{ old('telefono_contacto', auth()->user()?->telefono) }}" maxlength="40" required>
                            @error('telefono_contacto')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label for="observaciones" class="form-label">Observaciones <span class="text-muted fw-normal">(opcional)</span></label>
                            <textarea id="observaciones" name="observaciones" rows="3" maxlength="500"
                                      class="form-control @error('observaciones') is-invalid @enderror">{{ old('observaciones') }}</textarea>
                            @error('observaciones')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12 d-flex gap-2 mt-2">
                            <button type="submit" class="btn btn-principal">Enviar solicitud</button>
                            <a href="{{ route('escenarios.show', $escenario) }}" class="btn btn-link text-decoration-none">Volver</a>
                        </div>
                    </div>
                </form>
            </div>

            <div class="col-lg-4">
                <div class="tarjeta p-4">
                    <h2 class="h6 text-uppercase mb-3" style="letter-spacing:.08em; color: var(--gris);">
                        Fechas ocupadas
                    </h2>
                    @forelse ($ocupados as $rango)
                        <div class="d-flex justify-content-between border-bottom py-2 small">
                            <span>{{ \Carbon\Carbon::parse($rango['inicio'])->format('d/m/Y') }}</span>
                            <span class="text-muted">al {{ \Carbon\Carbon::parse($rango['fin'])->format('d/m/Y') }}</span>
                        </div>
                    @empty
                        <p class="ayuda-campo">Sin reservas para los próximos meses.</p>
                    @endforelse

                    @guest
                        <div class="alert alert-light border mt-3 mb-0 small">
                            ¿Quieres seguir tus reservas desde tu cuenta?
                            <a href="{{ route('registro') }}">Crea una</a> o
                            <a href="{{ route('login') }}">ingresa</a> antes de enviar.
                        </div>
                    @endguest

                    <div class="ayuda-campo mt-3">
                        La solicitud queda pendiente hasta que un asesor la confirme.
                        Mientras tanto, las fechas quedan bloqueadas a tu nombre.
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- Los datos van primero: reserva.js los lee al ejecutarse. --}}
    <script>
        window.datosReserva = {
            ocupados:  @json($ocupados),
            precioDia: {{ (float) $escenario->precio_dia }},
        };
    </script>
    <script src="{{ asset('js/reserva.js') }}"></script>
@endpush

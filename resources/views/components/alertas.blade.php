{{-- Mensajes de sesion, reutilizados por el sitio publico y el panel. --}}
@if (session('exito') || session('aviso') || $errors->any())
    <div class="container mt-4">
        @if (session('exito'))
            <div class="alert alert-success alert-dismissible fade show" role="alert" aria-live="polite">
                {{ session('exito') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        @endif

        @if (session('aviso'))
            <div class="alert alert-warning alert-dismissible fade show" role="alert" aria-live="polite">
                {{ session('aviso') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger" role="alert" aria-live="assertive">
                <p class="fw-semibold mb-2">Revisa lo siguiente:</p>
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
@endif

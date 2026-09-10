@props(['escenario'])

<article class="tarjeta">
    <img class="tarjeta__imagen"
         src="{{ app(\App\Services\AlmacenamientoImagenService::class)->url($escenario->imagen_principal) }}"
         alt="{{ $escenario->nombre }}" loading="lazy">

    <div class="tarjeta__cuerpo d-flex flex-column">
        <span class="etiqueta etiqueta--neutra mb-2 align-self-start">
            Hasta {{ number_format($escenario->capacidad, 0, ',', '.') }} personas
        </span>

        <h3 class="tarjeta__titulo">{{ $escenario->nombre }}</h3>
        <p class="tarjeta__texto flex-grow-1">{{ $escenario->resumen }}</p>

        <div class="d-flex align-items-end justify-content-between mt-3">
            <div>
                <div class="dato-destacado">{{ $escenario->precioFormateado() }}</div>
                <div class="ayuda-campo">por día</div>
            </div>
            <a href="{{ route('escenarios.show', $escenario) }}" class="btn btn-principal btn-sm">
                Ver detalle
            </a>
        </div>
    </div>
</article>

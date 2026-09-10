{{-- Mapa con Leaflet sobre OpenStreetMap: sin clave de API ni cuenta. --}}
@once
    @push('estilos')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
              integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    @endpush

    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
                integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const contenedor = document.getElementById('mapa');
                if (!contenedor || typeof L === 'undefined') return;

                const lat  = parseFloat(contenedor.dataset.lat);
                const lng  = parseFloat(contenedor.dataset.lng);
                const zoom = parseInt(contenedor.dataset.zoom, 10);

                const mapa = L.map('mapa').setView([lat, lng], zoom);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; colaboradores de OpenStreetMap',
                    maxZoom: 19,
                }).addTo(mapa);

                L.marker([lat, lng]).addTo(mapa).bindPopup(contenedor.dataset.etiqueta);
            });
        </script>
    @endpush
@endonce

<div id="mapa"
     data-lat="{{ config('services.mapa.lat') }}"
     data-lng="{{ config('services.mapa.lng') }}"
     data-zoom="{{ config('services.mapa.zoom') }}"
     data-etiqueta="{{ config('services.mapa.etiqueta') }}"
     role="img"
     aria-label="Mapa de ubicación de {{ config('services.mapa.etiqueta') }}"></div>

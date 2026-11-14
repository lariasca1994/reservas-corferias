{{-- Ícono de línea (24×24, decorativo) para el menú. Uso: <x-icono nombre="reservas" /> --}}
@props(['nombre'])
@php
    $trazos = match ($nombre) {
        'resumen'     => ['M3 3v18h18', 'M7 15l4-4 3 3 5-6'],
        'reservas'    => ['M8 2v4', 'M16 2v4', 'M3 10h18', 'M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z', 'm9 16 2 2 4-4'],
        'escenarios'  => ['M3 21h18', 'M5 21V8l7-5 7 5v13', 'M9 21v-6h6v6'],
        'eventos'     => ['M12 2l3 7h7l-5.5 4.5L18.5 21 12 16.5 5.5 21l2-7.5L2 9h7z'],
        'usuarios'    => ['M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2', 'M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z', 'M22 21v-2a4 4 0 0 0-3-3.87', 'M16 3.13a4 4 0 0 1 0 7.75'],
        'clave'       => ['M7.5 21a5.5 5.5 0 1 0 0-11 5.5 5.5 0 0 0 0 11Z', 'm21 2-9.6 9.6', 'm15.5 7.5 3 3L22 7l-3-3'],
        'seguridad'   => ['M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z', 'm9 12 2 2 4-4'],
        'salir'       => ['M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4', 'm16 17 5-5-5-5', 'M21 12H9'],
        'panel'       => ['M3 3h7v9H3z', 'M14 3h7v5h-7z', 'M14 12h7v9h-7z', 'M3 16h7v5H3z'],
        'ingresar'    => ['M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4', 'm10 17 5-5-5-5', 'M15 12H3'],
        'cuenta'      => ['M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2', 'M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z'],
        'mas'         => ['M5 12h.01', 'M12 12h.01', 'M19 12h.01'],
        'alternar'    => ['m11 17-5-5 5-5', 'm18 17-5-5 5-5'],
        default       => [],
    };
@endphp
<svg {{ $attributes->merge(['class' => 'icono']) }} viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor"
     stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    @foreach ($trazos as $d)<path d="{{ $d }}"/>@endforeach
</svg>

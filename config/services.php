<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Servicios de terceros
    |--------------------------------------------------------------------------
    |
    | El mapa usa Leaflet sobre teselas de OpenStreetMap. No requiere clave
    | de API ni cuenta de facturación, a diferencia de Google Maps, que era
    | lo que usaba la version original de 2019 con la clave escrita dentro
    | de la plantilla Blade.
    |
    */

    'mapa' => [
        'lat'      => env('MAPA_LAT', 4.62967),
        'lng'      => env('MAPA_LNG', -74.09045),
        'zoom'     => env('MAPA_ZOOM', 16),
        'etiqueta' => env('MAPA_ETIQUETA', 'Centro de Convenciones Corferias'),
    ],

    'brevo' => [
        'key' => env('BREVO_API_KEY'),
    ],

    // Aviso por Telegram de cada correo (ver App\Services\AvisoTelegram).
    'telegram' => [
        'token'   => env('TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('TELEGRAM_CHAT_ID'),
    ],

];

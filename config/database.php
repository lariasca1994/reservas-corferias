<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Conexion por defecto
    |--------------------------------------------------------------------------
    |
    | En desarrollo y produccion se usa sqlsrv contra Azure SQL Database.
    | La suite de pruebas fuerza la conexion 'testing' (SQLite en memoria)
    | desde phpunit.xml, de modo que las pruebas corren sin credenciales ni
    | conexion a internet.
    |
    */

    'default' => env('DB_CONNECTION', 'sqlsrv'),

    'connections' => [

        /*
        | Azure SQL Database.
        |
        | Encrypt es obligatorio contra Azure. TrustServerCertificate debe
        | quedar en false: Azure presenta un certificado valido y aceptarlo
        | a ciegas anularia la proteccion contra suplantacion.
        */
        'sqlsrv' => [
            'driver'                   => 'sqlsrv',
            'url'                      => env('DB_URL'),
            'host'                     => env('DB_HOST', 'localhost'),
            'port'                     => env('DB_PORT', '1433'),
            'database'                 => env('DB_DATABASE', 'reservas'),
            'username'                 => env('DB_USERNAME'),
            'password'                 => env('DB_PASSWORD'),
            'charset'                  => env('DB_CHARSET', 'utf8'),
            'prefix'                   => '',
            'prefix_indexes'           => true,
            'encrypt'                  => env('DB_ENCRYPT', 'yes'),
            'trust_server_certificate' => env('DB_TRUST_SERVER_CERTIFICATE', 'false'),

            // Azure SQL serverless se pausa por inactividad y tarda unos
            // segundos en despertar: sin este margen la primera peticion
            // del dia falla por timeout.
            //
            // Se usa 'login_timeout' y no PDO::ATTR_TIMEOUT porque el
            // controlador pdo_sqlsrv rechaza ese atributo con un error de
            // "invalid attribute". Laravel traduce esta clave a LoginTimeout
            // en la cadena de conexion.
            'login_timeout' => (int) env('DB_LOGIN_TIMEOUT', 30),
        ],

        /* Usada por phpunit.xml. No requiere archivo ni servidor. */
        'testing' => [
            'driver'                  => 'sqlite',
            'database'                => ':memory:',
            'prefix'                  => '',
            'foreign_key_constraints' => true,
        ],

        /* Alternativa local: contenedor mssql de docker-compose. */
        'sqlsrv_local' => [
            'driver'                   => 'sqlsrv',
            'host'                     => env('DB_HOST', 'mssql'),
            'port'                     => env('DB_PORT', '1433'),
            'database'                 => env('DB_DATABASE', 'reservas'),
            'username'                 => env('DB_USERNAME', 'sa'),
            'password'                 => env('DB_PASSWORD'),
            'charset'                  => 'utf8',
            'prefix'                   => '',
            'prefix_indexes'           => true,
            'encrypt'                  => 'yes',
            'trust_server_certificate' => 'true',
        ],

    ],

    'migrations' => [
        'table'                  => 'migrations',
        'update_date_on_publish' => true,
    ],

    'redis' => [
        'client'  => env('REDIS_CLIENT', 'phpredis'),
        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix'  => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
        ],
        'default' => [
            'url'      => env('REDIS_URL'),
            'host'     => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port'     => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],
        'cache' => [
            'url'      => env('REDIS_URL'),
            'host'     => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port'     => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],
    ],

];

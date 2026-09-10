<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Verificacion de conectividad contra la base de datos.
 *
 *   php artisan db:ping
 *
 * Confirma tres cosas de un solo golpe: que la extension pdo_sqlsrv este
 * cargada, que las credenciales del .env sean correctas y que la regla de
 * firewall de Azure permita la IP desde la que se ejecuta.
 */
class DbPing extends Command
{
    protected $signature = 'db:ping';

    protected $description = 'Comprueba la conexion con la base de datos configurada';

    public function handle(): int
    {
        $conexion = config('database.default');
        $host     = config("database.connections.{$conexion}.host");
        $base     = config("database.connections.{$conexion}.database");

        $this->line("Conexion : {$conexion}");
        $this->line("Servidor : {$host}");
        $this->line("Base     : {$base}");
        $this->newLine();

        if ($conexion === 'sqlsrv' && ! extension_loaded('pdo_sqlsrv')) {
            $this->error('La extension pdo_sqlsrv no esta cargada en este PHP.');

            return self::FAILURE;
        }

        try {
            $inicio  = microtime(true);
            $version = DB::selectOne('SELECT @@VERSION AS v')->v;
            $ms      = (int) round((microtime(true) - $inicio) * 1000);

            $this->info("Conexion establecida en {$ms} ms");
            $this->line((string) strtok($version, "\n"));

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('No fue posible conectar.');
            $this->line($e->getMessage());
            $this->newLine();
            $this->comment('Revisa en este orden:');
            $this->line('  1. Que la base en Azure no este pausada (portal, pestana Overview)');
            $this->line('  2. Que tu IP publica actual este en las reglas de firewall');
            $this->line('  3. Que DB_USERNAME y DB_PASSWORD del .env sean correctos');

            return self::FAILURE;
        }
    }
}

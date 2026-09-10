<?php

namespace App\Console\Commands;

use App\Models\Escenario;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Revision rapida de los puntos que suelen fallar tras la instalacion.
 *
 *   php artisan proyecto:revisar
 */
class RevisarProyecto extends Command
{
    protected $signature = 'proyecto:revisar';

    protected $description = 'Comprueba imagenes, almacenamiento y cuentas del panel';

    /** Imagenes historicas que usan los seeders. */
    private const IMAGENES = [
        'slide1.jpg', 'slide2.jpg', 'slide3.jpg',
        'event1.jpg', 'event2.jpg', 'Filbo-2018.png',
    ];

    public function handle(): int
    {
        $problemas = 0;

        // ---------------------------------------------- imagenes del repo
        $this->info('Imágenes en public/images');

        foreach (self::IMAGENES as $imagen) {
            $existe = file_exists(public_path('images/'.$imagen));
            $this->line(($existe ? '  ok   ' : '  FALTA').'  '.$imagen);
            $problemas += $existe ? 0 : 1;
        }

        if ($problemas > 0) {
            $this->newLine();
            $this->warn('  Cópialas del proyecto original:');
            $this->line('  Copy-Item ..\ApplicationWeb\public\images\* public\images\ -Force');
        }

        // ------------------------------------------ enlace de storage
        $this->newLine();
        $this->info('Enlace de almacenamiento');

        if (is_dir(public_path('storage'))) {
            $this->line('  ok     public/storage existe');
        } else {
            $this->line('  FALTA  public/storage');
            $this->warn('  Ejecuta: php artisan storage:link');
            $this->warn('  En Windows puede requerir consola como administrador.');
            $problemas++;
        }

        // ------------------------------------------------ datos cargados
        $this->newLine();
        $this->info('Datos');

        $escenarios = Escenario::count();
        $this->line("  Escenarios: {$escenarios}");

        if ($escenarios === 0) {
            $this->warn('  Ejecuta: php artisan migrate --seed');
            $problemas++;
        }

        // ------------------------------------------ cuentas del panel
        $this->newLine();
        $this->info('Cuentas con acceso al panel');

        $gestores = User::delPanel()->get();

        if ($gestores->isEmpty()) {
            $this->line('  Ninguna');
            $this->warn('  Ejecuta: php artisan db:seed --class=Database\\Seeders\\UsuarioSeeder');
            $problemas++;
        } else {
            foreach ($gestores as $usuario) {
                $estado = $usuario->activo ? 'activa' : 'DESACTIVADA';
                $this->line("  {$usuario->email}  ·  {$usuario->etiquetaRol()}  ·  {$estado}");
            }
        }

        // -------------------------------------------------- resultado
        $this->newLine();

        if ($problemas === 0) {
            $this->info('Todo en orden.');

            return self::SUCCESS;
        }

        $this->error("Se encontraron {$problemas} punto(s) por resolver.");

        return self::FAILURE;
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Route;
use PDOException;
use RuntimeException;
use Tests\TestCase;

class BaseNoDisponibleTest extends TestCase
{
    private function rutaQueFalla(string $mensaje): void
    {
        Route::get('/_prueba-base', function () use ($mensaje) {
            throw new QueryException('sqlsrv', 'select 1', [], new PDOException($mensaje));
        });
    }

    public function test_base_sin_cuota_muestra_mantenimiento(): void
    {
        $this->rutaQueFalla('SQLSTATE[HY000]: This database has reached the monthly free amount allowance for the month');

        $this->get('/_prueba-base')
            ->assertStatus(503)
            ->assertHeader('Retry-After', '3600')
            ->assertSee('Estamos en mantenimiento');
    }

    public function test_base_sin_conexion_responde_json_503(): void
    {
        $this->rutaQueFalla('SQLSTATE[08001]: TCP Provider: Error code 0x2749');

        $this->getJson('/_prueba-base')->assertStatus(503)->assertJsonPath('mensaje', 'Servicio en mantenimiento. Intenta más tarde.');
    }

    public function test_otros_errores_no_se_ocultan(): void
    {
        $this->rutaQueFalla('SQLSTATE[42S02]: Invalid object name');
        $this->get('/_prueba-base')->assertStatus(500);

        Route::get('/_prueba-otro', fn () => throw new RuntimeException('fallo'));
        $this->get('/_prueba-otro')->assertStatus(500);
    }
}

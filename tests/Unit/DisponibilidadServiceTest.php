<?php

namespace Tests\Unit;

use App\Exceptions\EscenarioNoDisponibleException;
use App\Models\Escenario;
use App\Models\Reserva;
use App\Services\DisponibilidadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Pruebas de la regla de ocupacion.
 *
 * Esta es la pieza critica del dominio. El codigo original de 2019 usaba:
 *
 *     if ($dateStart >= $inicioReservado && $dateEnd <= $finReservado)
 *
 * que solo detecta el caso en que el rango solicitado queda totalmente
 * contenido dentro de uno ya reservado. Los cruces parciales pasaban como
 * disponibles, con el resultado de que un mismo escenario podia quedar
 * reservado dos veces para fechas superpuestas.
 *
 * La condicion correcta es: dos rangos [a1,a2] y [b1,b2] se solapan si y
 * solo si a1 <= b2 y a2 >= b1.
 */
class DisponibilidadServiceTest extends TestCase
{
    use RefreshDatabase;

    private DisponibilidadService $servicio;

    private Escenario $escenario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->servicio  = new DisponibilidadService();
        $this->escenario = Escenario::factory()->create();

        // Reserva de referencia para todos los casos: del 10 al 15.
        Reserva::factory()
            ->for($this->escenario)
            ->entre('2026-09-10', '2026-09-15')
            ->create();
    }

    /**
     * Matriz de solapamiento contra la reserva del 10 al 15.
     *
     * @return array<string, array{0: string, 1: string, 2: bool}>
     */
    public static function rangos(): array
    {
        return [
            // nombre del caso                       inicio        fin           disponible
            'termina el dia anterior'            => ['2026-09-05', '2026-09-09', true],
            'empieza el dia siguiente'           => ['2026-09-16', '2026-09-20', true],
            'muy anterior'                       => ['2026-08-01', '2026-08-05', true],
            'muy posterior'                      => ['2026-12-01', '2026-12-05', true],

            'identico'                           => ['2026-09-10', '2026-09-15', false],
            'contenido dentro'                   => ['2026-09-11', '2026-09-14', false],
            'contiene al reservado'              => ['2026-09-01', '2026-09-30', false],
            'cruza por el inicio'                => ['2026-09-05', '2026-09-12', false],
            'cruza por el final'                 => ['2026-09-13', '2026-09-20', false],
            'toca solo el primer dia'            => ['2026-09-01', '2026-09-10', false],
            'toca solo el ultimo dia'            => ['2026-09-15', '2026-09-25', false],
            'un solo dia dentro del rango'       => ['2026-09-12', '2026-09-12', false],
        ];
    }

    #[Test]
    #[DataProvider('rangos')]
    public function detecta_correctamente_los_cruces_de_fechas(
        string $inicio,
        string $fin,
        bool $esperadoDisponible,
    ): void {
        $this->assertSame(
            $esperadoDisponible,
            $this->servicio->estaDisponible($this->escenario, $inicio, $fin),
            "Rango {$inicio} a {$fin}"
        );
    }

    #[Test]
    public function la_condicion_original_de_2019_dejaba_pasar_los_cruces_parciales(): void
    {
        // Solicitud del 5 al 12: se cruza con la reserva del 10 al 15.
        $inicio = '2026-09-05';
        $fin    = '2026-09-12';

        // Condicion del codigo original, reproducida tal cual.
        $reservadoInicio = '2026-09-10';
        $reservadoFin    = '2026-09-15';
        $logicaDe2019    = ($inicio >= $reservadoInicio && $fin <= $reservadoFin);

        $this->assertFalse(
            $logicaDe2019,
            'La logica original no marcaba este cruce como ocupado.'
        );

        $this->assertFalse(
            $this->servicio->estaDisponible($this->escenario, $inicio, $fin),
            'La logica corregida si debe marcar este cruce como ocupado.'
        );
    }

    #[Test]
    public function una_reserva_cancelada_no_bloquea_el_calendario(): void
    {
        $otro = Escenario::factory()->create();

        Reserva::factory()
            ->for($otro)
            ->entre('2026-10-01', '2026-10-05')
            ->cancelada()
            ->create();

        $this->assertTrue(
            $this->servicio->estaDisponible($otro, '2026-10-02', '2026-10-04')
        );
    }

    #[Test]
    public function una_reserva_pendiente_si_bloquea_el_calendario(): void
    {
        $otro = Escenario::factory()->create();

        Reserva::factory()
            ->for($otro)
            ->entre('2026-10-01', '2026-10-05')
            ->pendiente()
            ->create();

        $this->assertFalse(
            $this->servicio->estaDisponible($otro, '2026-10-02', '2026-10-04')
        );
    }

    #[Test]
    public function las_reservas_de_otro_escenario_no_afectan_la_disponibilidad(): void
    {
        $otro = Escenario::factory()->create();

        $this->assertTrue(
            $this->servicio->estaDisponible($otro, '2026-09-10', '2026-09-15'),
            'La ocupacion debe evaluarse por escenario, no de forma global.'
        );
    }

    #[Test]
    public function puede_ignorar_una_reserva_al_reprogramarla(): void
    {
        $reserva = $this->escenario->reservas()->first();

        $this->assertFalse(
            $this->servicio->estaDisponible($this->escenario, '2026-09-10', '2026-09-15')
        );

        $this->assertTrue(
            $this->servicio->estaDisponible($this->escenario, '2026-09-10', '2026-09-15', $reserva->id),
            'Al editar una reserva, esa misma reserva no debe bloquearse a si misma.'
        );
    }

    #[Test]
    public function reservar_lanza_excepcion_si_el_rango_esta_ocupado(): void
    {
        $this->expectException(EscenarioNoDisponibleException::class);

        $this->servicio->reservar($this->escenario, [
            'nombre_contacto'   => 'Ana Gomez',
            'email_contacto'    => 'ana@ejemplo.com',
            'telefono_contacto' => '3001234567',
            'fecha_inicio'      => '2026-09-12',
            'fecha_fin'         => '2026-09-14',
        ]);
    }

    #[Test]
    public function reservar_persiste_la_reserva_con_codigo_y_estado_pendiente(): void
    {
        $reserva = $this->servicio->reservar($this->escenario, [
            'nombre_contacto'   => 'Ana Gomez',
            'email_contacto'    => 'ana@ejemplo.com',
            'telefono_contacto' => '3001234567',
            'fecha_inicio'      => '2026-11-01',
            'fecha_fin'         => '2026-11-03',
        ]);

        $this->assertDatabaseHas('reservas', [
            'id'     => $reserva->id,
            'estado' => Reserva::ESTADO_PENDIENTE,
        ]);

        $this->assertMatchesRegularExpression('/^RSV-[A-Z0-9]{8}$/', $reserva->codigo);
        $this->assertSame(3, $reserva->diasReservados());
    }

    #[Test]
    public function los_rangos_ocupados_excluyen_las_reservas_pasadas_y_canceladas(): void
    {
        $otro = Escenario::factory()->create();

        Reserva::factory()->for($otro)
            ->entre(now()->subDays(30)->toDateString(), now()->subDays(25)->toDateString())
            ->create();

        Reserva::factory()->for($otro)
            ->entre(now()->addDays(5)->toDateString(), now()->addDays(7)->toDateString())
            ->cancelada()
            ->create();

        $vigente = Reserva::factory()->for($otro)
            ->entre(now()->addDays(10)->toDateString(), now()->addDays(12)->toDateString())
            ->create();

        $ocupados = $this->servicio->rangosOcupados($otro);

        $this->assertCount(1, $ocupados);
        $this->assertSame($vigente->fecha_inicio->toDateString(), $ocupados->first()['inicio']);
    }
}

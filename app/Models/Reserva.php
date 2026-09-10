<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Reserva extends Model
{
    use HasFactory;

    public const ESTADO_PENDIENTE  = 'pendiente';
    public const ESTADO_CONFIRMADA = 'confirmada';
    public const ESTADO_CANCELADA  = 'cancelada';

    protected $table = 'reservas';

    protected $fillable = [
        'codigo',
        'escenario_id',
        'user_id',
        'nombre_contacto',
        'email_contacto',
        'telefono_contacto',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin'    => 'date',
        ];
    }

    /**
     * Genera el codigo publico si no viene dado.
     * Formato: RSV-AB12CD34 (prefijo fijo + 8 caracteres en mayuscula).
     */
    protected static function booted(): void
    {
        static::creating(function (self $reserva): void {
            if (blank($reserva->codigo)) {
                do {
                    $codigo = 'RSV-'.strtoupper(Str::random(8));
                } while (self::where('codigo', $codigo)->exists());

                $reserva->codigo = $codigo;
            }
        });
    }

    /** Las rutas publicas resuelven por codigo, no por id. */
    public function getRouteKeyName(): string
    {
        return 'codigo';
    }

    public function escenario(): BelongsTo
    {
        return $this->belongsTo(Escenario::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Solo las reservas que efectivamente bloquean el calendario. */
    public function scopeQueBloquean(Builder $query): Builder
    {
        return $query->whereIn('estado', [
            self::ESTADO_PENDIENTE,
            self::ESTADO_CONFIRMADA,
        ]);
    }

    /**
     * Reservas que se cruzan con el rango dado.
     *
     * Dos rangos [a1,a2] y [b1,b2] se solapan si y solo si
     * a1 <= b2 y a2 >= b1. El codigo de 2019 usaba
     * inicio >= reservaInicio && fin <= reservaFin, que solo detecta
     * el rango totalmente contenido y deja pasar cualquier cruce parcial.
     */
    public function scopeQueSeCruzanCon(Builder $query, string $inicio, string $fin): Builder
    {
        return $query->whereDate('fecha_inicio', '<=', $fin)
            ->whereDate('fecha_fin', '>=', $inicio);
    }

    /** Dias facturables: un evento de un solo dia cuenta como uno. */
    public function diasReservados(): int
    {
        // Carbon 3 devuelve float en diffInDays; se normaliza a entero.
        return (int) $this->fecha_inicio->diffInDays($this->fecha_fin) + 1;
    }

    public function total(): float
    {
        return $this->diasReservados() * (float) $this->escenario->precio_dia;
    }

    public function estaCancelada(): bool
    {
        return $this->estado === self::ESTADO_CANCELADA;
    }

    public function bloqueaCalendario(): bool
    {
        return in_array($this->estado, [self::ESTADO_PENDIENTE, self::ESTADO_CONFIRMADA], true);
    }

    /** Etiqueta legible para la interfaz. */
    public function etiquetaEstado(): string
    {
        return match ($this->estado) {
            self::ESTADO_PENDIENTE  => 'Pendiente de confirmación',
            self::ESTADO_CONFIRMADA => 'Confirmada',
            self::ESTADO_CANCELADA  => 'Cancelada',
            default                 => 'Desconocido',
        };
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Escenario extends Model
{
    use HasFactory;

    protected $table = 'escenarios';

    protected $fillable = [
        'slug',
        'nombre',
        'resumen',
        'descripcion',
        'precio_dia',
        'capacidad',
        'imagen_principal',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'precio_dia' => 'decimal:2',
            'capacidad'  => 'integer',
            'activo'     => 'boolean',
        ];
    }

    /** La ruta usa el slug en lugar del id autoincremental. */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function caracteristicas(): HasMany
    {
        return $this->hasMany(Caracteristica::class)->orderBy('orden');
    }

    public function imagenes(): HasMany
    {
        return $this->hasMany(ImagenEscenario::class)->orderBy('orden');
    }

    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class);
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(Evento::class);
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    /** Precio formateado en pesos colombianos, sin decimales. */
    public function precioFormateado(): string
    {
        return '$ '.number_format((float) $this->precio_dia, 0, ',', '.');
    }
}

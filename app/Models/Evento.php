<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Evento extends Model
{
    use HasFactory;

    protected $table = 'eventos';

    protected $fillable = [
        'slug',
        'nombre',
        'resumen',
        'descripcion',
        'fecha_inicio',
        'fecha_fin',
        'horario',
        'imagen',
        'escenario_id',
        'destacado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin'    => 'date',
            'destacado'    => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function escenario(): BelongsTo
    {
        return $this->belongsTo(Escenario::class);
    }

    /** Eventos que aun no han terminado, ordenados por proximidad. */
    public function scopeVigentes(Builder $query): Builder
    {
        return $query->whereDate('fecha_fin', '>=', now()->toDateString())
            ->orderBy('fecha_inicio');
    }

    public function scopeDestacados(Builder $query): Builder
    {
        return $query->where('destacado', true);
    }
}

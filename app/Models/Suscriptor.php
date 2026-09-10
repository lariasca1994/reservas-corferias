<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Suscriptor extends Model
{
    use HasFactory;

    protected $table = 'suscriptores';

    protected $fillable = [
        'email',
        'nombre',
        'token_baja',
        'confirmado_en',
        'baja_en',
    ];

    protected function casts(): array
    {
        return [
            'confirmado_en' => 'datetime',
            'baja_en'       => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $suscriptor): void {
            if (blank($suscriptor->token_baja)) {
                $suscriptor->token_baja = Str::random(48);
            }
        });
    }

    /** Solo quienes siguen suscritos reciben envios. */
    public function scopeActivos(Builder $query): Builder
    {
        return $query->whereNull('baja_en');
    }

    public function darDeBaja(): void
    {
        $this->update(['baja_en' => now()]);
    }

    public function estaDadoDeBaja(): bool
    {
        return $this->baja_en !== null;
    }
}

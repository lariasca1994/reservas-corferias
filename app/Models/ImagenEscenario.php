<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImagenEscenario extends Model
{
    protected $table = 'escenario_imagenes';

    protected $fillable = [
        'escenario_id',
        'ruta',
        'texto_alternativo',
        'orden',
    ];

    protected function casts(): array
    {
        return [
            'orden' => 'integer',
        ];
    }

    public function escenario(): BelongsTo
    {
        return $this->belongsTo(Escenario::class);
    }
}

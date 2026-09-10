<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Caracteristica extends Model
{
    protected $table = 'escenario_caracteristicas';

    protected $fillable = [
        'escenario_id',
        'titulo',
        'descripcion',
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

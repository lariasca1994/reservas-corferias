<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    public const ROL_ADMINISTRADOR = 'administrador';
    public const ROL_OPERADOR      = 'operador';
    public const ROL_CLIENTE       = 'cliente';

    /** @var list<string> */
    protected $fillable = [
        'name',
        'email',
        'password',
        'rol',
        'activo',
        'telefono',
    ];

    /** @var list<string> */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'activo'            => 'boolean',
        ];
    }

    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class);
    }

    public function esAdministrador(): bool
    {
        return $this->rol === self::ROL_ADMINISTRADOR;
    }

    /** Operadores y administradores pueden entrar al panel. */
    public function puedeGestionar(): bool
    {
        return $this->activo && in_array(
            $this->rol,
            [self::ROL_ADMINISTRADOR, self::ROL_OPERADOR],
            true
        );
    }

    public function scopeDelPanel(Builder $query): Builder
    {
        return $query->whereIn('rol', [self::ROL_ADMINISTRADOR, self::ROL_OPERADOR]);
    }

    public function etiquetaRol(): string
    {
        return match ($this->rol) {
            self::ROL_ADMINISTRADOR => 'Administrador',
            self::ROL_OPERADOR      => 'Operador',
            default                 => 'Cliente',
        };
    }
}

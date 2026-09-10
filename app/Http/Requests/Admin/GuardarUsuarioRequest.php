<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class GuardarUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // el middleware 'administrador' ya restringio el acceso
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $usuario = $this->route('usuario');

        return [
            'name'     => ['required', 'string', 'min:3', 'max:120'],
            'email'    => [
                'required', 'email:rfc', 'max:150',
                Rule::unique('users', 'email')->ignore($usuario?->id),
            ],
            'telefono' => ['nullable', 'string', 'max:40'],
            'rol'      => ['required', Rule::in([
                User::ROL_ADMINISTRADOR,
                User::ROL_OPERADOR,
                User::ROL_CLIENTE,
            ])],
            'activo'   => ['boolean'],

            // Al crear es obligatoria; al editar, solo si se quiere cambiar.
            'password' => [
                $usuario ? 'nullable' : 'required',
                'confirmed',
                Password::min(10)->letters()->numbers(),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'email.unique'       => 'Ya existe una cuenta con este correo electrónico.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['activo' => $this->boolean('activo')]);
    }
}

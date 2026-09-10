<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Registro publico de clientes.
 *
 * A diferencia del formulario administrativo, aqui el rol no se recibe del
 * cliente: se asigna en el controlador. Aceptarlo por parametro permitiria
 * que cualquiera se registrara como administrador.
 */
class RegistroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'min:3', 'max:120'],
            'email'    => ['required', 'email:rfc', 'max:150', Rule::unique('users', 'email')],
            'telefono' => ['nullable', 'string', 'max:40'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique'       => 'Ya existe una cuenta con este correo. ¿Quieres ingresar?',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name'     => 'nombre',
            'email'    => 'correo electrónico',
            'password' => 'contraseña',
        ];
    }
}

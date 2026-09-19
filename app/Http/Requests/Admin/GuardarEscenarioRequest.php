<?php

namespace App\Http\Requests\Admin;

use App\Services\AlmacenamientoImagenService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarEscenarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // el middleware 'gestor' ya restringio el acceso
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $escenario = $this->route('escenario');

        return [
            'nombre' => ['required', 'string', 'min:3', 'max:120'],
            'slug'   => [
                'required', 'string', 'max:60', 'regex:/^[a-z0-9\-]+$/',
                Rule::unique('escenarios', 'slug')->ignore($escenario?->id),
            ],
            'resumen'     => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:4000'],
            'precio_dia'  => ['required', 'numeric', 'min:0', 'max:999999999'],
            'capacidad'   => ['required', 'integer', 'min:1', 'max:100000'],
            'activo'      => ['boolean'],

            'imagen' => [
                $escenario ? 'nullable' : 'required',
                'image',
                'mimes:'.implode(',', AlmacenamientoImagenService::EXTENSIONES),
                'max:'.AlmacenamientoImagenService::MAX_KB,
                'dimensions:min_width=600,min_height=400',
            ],

            'caracteristicas'               => ['array', 'max:10'],
            'caracteristicas.*.titulo'      => ['required_with:caracteristicas', 'string', 'max:120'],
            'caracteristicas.*.descripcion' => ['required_with:caracteristicas', 'string', 'max:400'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slug.regex'        => 'El identificador solo admite minúsculas, números y guiones.',
            'imagen.dimensions' => 'La imagen debe medir al menos 600 x 400 píxeles.',
            'imagen.max'        => 'La imagen no puede superar los 3 MB.',
            'imagen.mimes'      => 'Formatos aceptados: JPG, PNG o WEBP.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'activo' => $this->boolean('activo'),
            'slug'   => str($this->input('slug', $this->input('nombre')))->slug()->toString(),
        ]);
    }
}

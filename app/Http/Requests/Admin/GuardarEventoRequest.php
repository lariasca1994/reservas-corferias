<?php

namespace App\Http\Requests\Admin;

use App\Services\AlmacenamientoImagenService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarEventoRequest extends FormRequest
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
        $evento = $this->route('evento');

        return [
            'nombre' => ['required', 'string', 'min:3', 'max:150'],
            'slug'   => [
                'required', 'string', 'max:80', 'regex:/^[a-z0-9\-]+$/',
                Rule::unique('eventos', 'slug')->ignore($evento?->id),
            ],
            'resumen'      => ['required', 'string', 'max:255'],
            'descripcion'  => ['required', 'string', 'max:8000'],
            'fecha_inicio' => ['required', 'date_format:Y-m-d'],
            'fecha_fin'    => ['required', 'date_format:Y-m-d', 'after_or_equal:fecha_inicio'],
            'horario'      => ['required', 'string', 'max:80'],
            'escenario_id' => ['nullable', 'integer', Rule::exists('escenarios', 'id')],
            'destacado'    => ['boolean'],

            'imagen' => [
                $evento ? 'nullable' : 'required',
                'image',
                'mimes:'.implode(',', AlmacenamientoImagenService::EXTENSIONES),
                'max:'.AlmacenamientoImagenService::MAX_KB,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'fecha_fin.after_or_equal' => 'La fecha final no puede ser anterior a la de inicio.',
            'slug.regex'               => 'El identificador solo admite minúsculas, números y guiones.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'destacado' => $this->boolean('destacado'),
            'slug'      => str($this->input('slug', $this->input('nombre')))->slug()->toString(),
        ]);
    }
}

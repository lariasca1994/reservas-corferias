<?php

namespace App\Http\Requests;

use App\Models\Escenario;
use App\Services\DisponibilidadService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validacion del formulario de reserva.
 *
 * El formulario original no validaba nada del lado del servidor: el
 * controlador tomaba directamente request->input('date-start') y lo
 * comparaba como cadena, sin comprobar que fuera una fecha, que el fin
 * fuera posterior al inicio, ni que el rango estuviera en el futuro.
 */
class GuardarReservaRequest extends FormRequest
{
    /** Numero maximo de dias que puede abarcar una sola reserva. */
    private const MAX_DIAS = 30;

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
            'nombre_contacto'   => ['required', 'string', 'min:3', 'max:120'],
            'email_contacto'    => ['required', 'email:rfc', 'max:150'],
            'telefono_contacto' => ['required', 'string', 'min:7', 'max:40'],
            'fecha_inicio'      => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'fecha_fin'         => ['required', 'date_format:Y-m-d', 'after_or_equal:fecha_inicio'],
            'observaciones'     => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'fecha_inicio.after_or_equal' => 'La fecha de inicio no puede estar en el pasado.',
            'fecha_fin.after_or_equal'    => 'La fecha final debe ser igual o posterior a la de inicio.',
            'email_contacto.email'        => 'El correo electrónico no tiene un formato válido.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nombre_contacto'   => 'nombre',
            'email_contacto'    => 'correo electrónico',
            'telefono_contacto' => 'teléfono',
            'fecha_inicio'      => 'fecha de inicio',
            'fecha_fin'         => 'fecha final',
        ];
    }

    /**
     * Reglas que dependen de varios campos a la vez o de la base de datos.
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['fecha_inicio', 'fecha_fin'])) {
                    return;
                }

                $inicio = $this->string('fecha_inicio')->toString();
                $fin    = $this->string('fecha_fin')->toString();

                $dias = now()->parse($inicio)->diffInDays(now()->parse($fin)) + 1;

                if ($dias > self::MAX_DIAS) {
                    $validator->errors()->add(
                        'fecha_fin',
                        'Una reserva no puede superar '.self::MAX_DIAS.' días. Para eventos más largos, contáctanos directamente.'
                    );

                    return;
                }

                $escenario = $this->route('escenario');

                if (! $escenario instanceof Escenario) {
                    return;
                }

                $servicio = app(DisponibilidadService::class);

                if ($servicio->estaDisponible($escenario, $inicio, $fin)) {
                    return;
                }

                $cruces = $servicio->crucesCon($escenario, $inicio, $fin)
                    ->map(fn ($reserva) => $reserva->fecha_inicio->format('d/m/Y').' al '.$reserva->fecha_fin->format('d/m/Y'))
                    ->implode(', ');

                $validator->errors()->add(
                    'fecha_inicio',
                    'Estas fechas se cruzan con una reserva existente ('.$cruces.'). Elige otro rango o revisa nuestros demás escenarios.'
                );
            },
        ];
    }
}

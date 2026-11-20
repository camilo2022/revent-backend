<?php

namespace App\Http\Requests\Api\Integration\EffiSync;

use App\Http\Requests\Api\Integration\IntegrationRequest;

/**
 * Lanzar la sincronización con Effi: confirmación escrita, decisiones de la persona (categorías de los artículos
 * nuevos por modelo y qué artículo inhabilitar en los duplicados) y, opcional, un solo código para probar.
 */
class SyncRequest extends IntegrationRequest
{
    public function rules(): array
    {
        return [
            'confirmacion' => ['required', 'string', 'in:SINCRONIZAR'],
            'solo' => ['nullable', 'string', 'max:60'],
            'decisiones' => ['nullable', 'array'],
            'decisiones.modelos' => ['nullable', 'array'],
            'decisiones.modelos.*.accion' => ['required', 'string', 'in:existente,crear,ninguna'],
            'decisiones.modelos.*.categoria' => ['nullable', 'integer', 'required_if:decisiones.modelos.*.accion,existente'],
            'decisiones.duplicados' => ['nullable', 'array'],
            'decisiones.duplicados.*.inhabilitar' => ['nullable'],
            'decisiones.duplicados.*.ninguno' => ['nullable', 'boolean'],
        ];
    }

    /** Las decisiones con la misma forma que recibe EffiInventorySyncPlanner::applyDecisions (igual que la pantalla web). */
    public function decisions(): array
    {
        return [
            'modelos' => array_filter((array) $this->input('decisiones.modelos', []), fn ($v) => is_array($v) && in_array($v['accion'] ?? null, ['existente', 'crear', 'ninguna'], true)),
            'duplicados' => array_filter((array) $this->input('decisiones.duplicados', []), fn ($v) => is_array($v) && (isset($v['inhabilitar']) || !empty($v['ninguno']))),
        ];
    }

    /** Código para probar con uno solo, o null. */
    public function testCode(): ?string
    {
        $only = trim((string) $this->input('solo'));

        return $only === '' ? null : $only;
    }

    public function messages(): array
    {
        return [
            ...parent::messages(),
            'confirmacion.in' => 'Escribe SINCRONIZAR para confirmar.',
            'decisiones.modelos.*.categoria.required_if' => 'Elige la categoría existente del modelo.',
        ];
    }

    public function attributes(): array
    {
        return [
            ...parent::attributes(),
            'confirmacion' => 'Confirmación',
            'solo' => 'Código de prueba',
            'decisiones' => 'Decisiones',
        ];
    }
}

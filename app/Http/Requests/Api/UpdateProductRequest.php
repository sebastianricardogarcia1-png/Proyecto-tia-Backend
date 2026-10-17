<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Preparar y normalizar campos recibidos desde React (camelCase o snake_case).
     */
    protected function prepareForValidation(): void
    {
        $dataToMerge = [];

        if ($this->has('nombre') || $this->has('name')) {
            $dataToMerge['nombre'] = trim($this->input('nombre', $this->input('name', '')));
        }
        if ($this->has('publico') || $this->has('audience')) {
            $dataToMerge['publico'] = strtolower(trim($this->input('publico', $this->input('audience', 'mujer'))));
        }
        if ($this->has('categoria') || $this->has('category')) {
            $dataToMerge['categoria'] = strtolower(trim($this->input('categoria', $this->input('category', 'zapatos'))));
        }
        if ($this->has('descripcion') || $this->has('description')) {
            $dataToMerge['descripcion'] = trim($this->input('descripcion', $this->input('description', ''))) ?: null;
        }
        if ($this->has('es_nuevo') || $this->has('isNew')) {
            $dataToMerge['es_nuevo'] = $this->boolean('es_nuevo', $this->boolean('isNew', false));
        }
        if ($this->has('es_destacado') || $this->has('isFeatured')) {
            $dataToMerge['es_destacado'] = $this->boolean('es_destacado', $this->boolean('isFeatured', false));
        }
        if ($this->has('activo') || $this->has('active')) {
            $dataToMerge['activo'] = $this->boolean('activo', $this->boolean('active', true));
        }
        if ($this->has('referencia') || $this->has('reference')) {
            $dataToMerge['referencia'] = trim($this->input('referencia', $this->input('reference', ''))) ?: null;
        }

        if ($this->has('variantes') || $this->has('variants')) {
            $rawVariants = $this->input('variantes', $this->input('variants', []));
            $normalizedVariants = [];
            if (is_array($rawVariants)) {
                foreach ($rawVariants as $index => $v) {
                    if (!is_array($v)) continue;
                    $normalizedVariants[] = [
                        'id' => $v['id'] ?? null,
                        'color' => trim($v['color'] ?? 'Estilo Principal'),
                        'precio' => (int) ($v['precio'] ?? $v['price'] ?? 0),
                        'imagen_url' => $v['imagen_url'] ?? $v['imageUrl'] ?? null,
                        'disponible' => isset($v['disponible'])
                            ? (bool) $v['disponible']
                            : (isset($v['available']) ? (bool) $v['available'] : true),
                        'referencia' => trim($v['referencia'] ?? $v['reference'] ?? '') ?: null,
                        'orden' => (int) ($v['orden'] ?? $v['order'] ?? $index),
                    ];
                }
            }
            $dataToMerge['variantes'] = $normalizedVariants;
        }

        $this->merge($dataToMerge);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['sometimes', 'required', 'string', 'max:255'],
            'publico' => ['sometimes', 'required', 'in:mujer,hombre'],
            'categoria' => ['sometimes', 'required', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string'],
            'es_nuevo' => ['boolean'],
            'es_destacado' => ['boolean'],
            'activo' => ['boolean'],
            'referencia' => ['nullable', 'string', 'max:100'],
            'variantes' => ['sometimes', 'array', 'min:1'],
            'variantes.*.id' => ['nullable'],
            'variantes.*.color' => ['required', 'string', 'max:100'],
            'variantes.*.precio' => ['required', 'numeric', 'min:0'],
            'variantes.*.imagen_url' => ['nullable', 'string'],
            'variantes.*.disponible' => ['boolean'],
            'variantes.*.referencia' => ['nullable', 'string', 'max:100'],
            'variantes.*.orden' => ['integer'],
        ];
    }
}

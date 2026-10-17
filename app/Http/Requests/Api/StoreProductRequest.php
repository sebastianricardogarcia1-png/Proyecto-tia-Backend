<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
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

        $nombre = $this->input('nombre', $this->input('name'));
        $publico = $this->input('publico', $this->input('audience'));
        $categoria = $this->input('categoria', $this->input('category'));
        $descripcion = $this->input('descripcion', $this->input('description'));
        $referencia = $this->input('referencia', $this->input('reference'));

        $this->merge([
            'nombre' => is_string($nombre) ? trim($nombre) : $nombre,
            'publico' => is_string($publico) ? strtolower(trim($publico)) : $publico,
            'categoria' => is_string($categoria) ? strtolower(trim($categoria)) : $categoria,
            'descripcion' => is_string($descripcion) ? (trim($descripcion) ?: null) : null,
            'es_nuevo' => $this->boolean('es_nuevo', $this->boolean('isNew', false)),
            'es_destacado' => $this->boolean('es_destacado', $this->boolean('isFeatured', false)),
            'activo' => $this->boolean('activo', $this->boolean('active', true)),
            'referencia' => is_string($referencia) ? (trim($referencia) ?: null) : null,
            'variantes' => $normalizedVariants,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'publico' => ['required', 'in:mujer,hombre'],
            'categoria' => ['required', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string'],
            'es_nuevo' => ['boolean'],
            'es_destacado' => ['boolean'],
            'activo' => ['boolean'],
            'referencia' => ['nullable', 'string', 'max:100'],
            'variantes' => ['required', 'array', 'min:1'],
            'variantes.*.color' => ['required', 'string', 'max:100'],
            'variantes.*.precio' => ['required', 'numeric', 'min:0'],
            'variantes.*.imagen_url' => ['nullable', 'string'],
            'variantes.*.disponible' => ['boolean'],
            'variantes.*.referencia' => ['nullable', 'string', 'max:100'],
            'variantes.*.orden' => ['integer'],
        ];
    }
}

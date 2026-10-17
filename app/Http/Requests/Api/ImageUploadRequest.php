<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ImageUploadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        if ($this->hasFile('image')) {
            return [
                'image' => ['required', 'file', 'image', 'mimes:webp,png,jpg,jpeg', 'max:5120'],
            ];
        }

        return [
            'image' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if (! is_string($value) || ! preg_match('/^data:image\/(webp|jpeg|png|jpg);base64,/', $value)) {
                        $fail('La imagen debe ser un archivo (WebP, JPG, PNG) o una cadena en formato base64 válida.');
                    }
                },
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'image.required' => 'La imagen es obligatoria.',
            'image.file' => 'El archivo proporcionado no es válido.',
            'image.image' => 'El archivo debe ser una imagen.',
            'image.mimes' => 'El formato de la imagen debe ser WebP, JPG, JPEG o PNG.',
            'image.max' => 'La imagen no debe superar los 5 MB (5120 KB).',
        ];
    }
}

<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SavePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route is under `auth` middleware. If roles are added — Policy.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'body' => ['nullable', 'array'],
            'body.uz' => ['nullable', 'string'],
            'body.ru' => ['nullable', 'string'],
            'body.kk' => ['nullable', 'string'],
            // SVG is excluded (may contain JS — stored XSS).
            'cover' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'body.uz' => __('Matn (o‘zbekcha)'),
            'body.ru' => __('Matn (ruscha)'),
            'body.kk' => __('Matn (qoraqalpoqcha)'),
            'cover' => __('Muqova rasm'),
        ];
    }
}

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
            'title' => ['nullable', 'array'],
            'title.uz' => ['nullable', 'string', 'max:255'],
            'title.ru' => ['nullable', 'string', 'max:255'],
            'title.kk' => ['nullable', 'string', 'max:255'],

            'body' => ['nullable', 'array'],
            'body.uz' => ['nullable', 'string'],
            'body.ru' => ['nullable', 'string'],
            'body.kk' => ['nullable', 'string'],

            // SVG is excluded (may contain JS — stored XSS).
            'cover' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:2048'],
            'remove_cover' => ['nullable', 'boolean'],
            'gallery' => ['nullable', 'array'],
            'gallery.*' => ['image', 'mimes:jpeg,jpg,png,webp,gif', 'max:5120'],
            'remove_gallery_ids' => ['nullable', 'array'],
            'remove_gallery_ids.*' => ['integer', 'exists:page_images,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title.uz' => __('Sarlavha (o‘zbekcha)'),
            'title.ru' => __('Sarlavha (ruscha)'),
            'title.kk' => __('Sarlavha (qoraqalpoqcha)'),
            'body.uz' => __('Matn (o‘zbekcha)'),
            'body.ru' => __('Matn (ruscha)'),
            'body.kk' => __('Matn (qoraqalpoqcha)'),
            'cover' => __('Muqova rasm'),
        ];
    }
}

<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateNewsRequest extends FormRequest
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
            'title' => ['required', 'array'],
            'title.uz' => ['nullable', 'string', 'max:255'],
            'title.ru' => ['nullable', 'string', 'max:255'],
            'title.kk' => ['nullable', 'string', 'max:255'],

            'excerpt' => ['nullable', 'array'],
            'excerpt.uz' => ['nullable', 'string'],
            'excerpt.ru' => ['nullable', 'string'],
            'excerpt.kk' => ['nullable', 'string'],

            'body' => ['required', 'array'],
            'body.uz' => ['nullable', 'string'],
            'body.ru' => ['nullable', 'string'],
            'body.kk' => ['nullable', 'string'],

            'news_category_id' => ['required', 'integer', 'exists:news_categories,id'],

            // SVG is excluded (may contain JS — stored XSS).
            'cover' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:2048'],
            'gallery' => ['nullable', 'array'],
            'gallery.*' => ['image', 'mimes:jpeg,jpg,png,webp,gif', 'max:2048'],

            'published_at' => ['nullable', 'date'],
        ];
    }

    /**
     * At least one language (uz/ru/kk) must be filled — for title and body.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->allEmpty('title')) {
                $validator->errors()->add('title.uz', __('Kamida bitta tilda sarlavha kiriting.'));
            }

            if ($this->allEmpty('body')) {
                $validator->errors()->add('body.uz', __('Kamida bitta tilda matn kiriting.'));
            }
        });
    }

    /**
     * Are all three languages of the given translatable field empty?
     */
    private function allEmpty(string $field): bool
    {
        $values = array_map('trim', array_map('strval', (array) $this->input($field, [])));

        return count(array_filter($values, static fn (string $v): bool => $v !== '')) === 0;
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
            'news_category_id' => __('Kategoriya'),
            'cover' => __('Muqova rasm'),
            'published_at' => __('Nashr sanasi'),
        ];
    }
}

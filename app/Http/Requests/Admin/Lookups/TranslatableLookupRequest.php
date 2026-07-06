<?php

namespace App\Http\Requests\Admin\Lookups;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for translatable lookups (book_type, language, location).
 * In the admin panel all 3 languages are REQUIRED (a full translation is required).
 */
class TranslatableLookupRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route is under `auth` middleware. When roles are added — Policy.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'array'],
            'name.uz' => ['required', 'string', 'max:255'],
            'name.ru' => ['required', 'string', 'max:255'],
            'name.kk' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name.uz' => __('Nomi (o‘zbekcha)'),
            'name.ru' => __('Nomi (ruscha)'),
            'name.kk' => __('Nomi (qoraqalpoqcha)'),
        ];
    }
}

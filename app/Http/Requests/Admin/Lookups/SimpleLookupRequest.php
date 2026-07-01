<?php

namespace App\Http\Requests\Admin\Lookups;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Oddiy (tarjimasiz) lookup'lar (publisher, author) uchun validatsiya.
 * `name` — oddiy string.
 */
class SimpleLookupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // admin `auth` middleware ostida
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => __('Nomi'),
        ];
    }
}

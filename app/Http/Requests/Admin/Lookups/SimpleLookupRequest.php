<?php

namespace App\Http\Requests\Admin\Lookups;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for simple (non-translatable) lookups (publisher, author).
 * `name` — a plain string.
 */
class SimpleLookupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // admin is under the `auth` middleware
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

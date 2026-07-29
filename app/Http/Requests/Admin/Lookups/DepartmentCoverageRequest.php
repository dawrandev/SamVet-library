<?php

namespace App\Http\Requests\Admin\Lookups;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for the department-coverage lookup — translatable name (all 3
 * languages required, like other translatable lookups) plus a 0-100 percentage.
 */
class DepartmentCoverageRequest extends FormRequest
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
            'percentage' => ['required', 'integer', 'min:0', 'max:100'],
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
            'percentage' => __('Ta’minganlik darajasi (%)'),
        ];
    }
}

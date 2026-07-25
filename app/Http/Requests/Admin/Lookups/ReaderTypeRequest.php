<?php

namespace App\Http\Requests\Admin\Lookups;

use Illuminate\Foundation\Http\FormRequest;

class ReaderTypeRequest extends FormRequest
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
            'is_student' => ['nullable', 'boolean'],
            'certificate_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => __('Nomi'),
            'is_student' => __('Talaba turi'),
            'certificate_color' => __('Guvohnoma rangi'),
        ];
    }
}

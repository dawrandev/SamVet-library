<?php

namespace App\Http\Requests\Admin;

use App\Services\LookupService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLookupRequest extends FormRequest
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
            'type' => ['required', 'string', Rule::in(LookupService::types())],
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => __('Nom'),
        ];
    }
}

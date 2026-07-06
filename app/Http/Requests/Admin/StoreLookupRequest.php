<?php

namespace App\Http\Requests\Admin;

use App\Services\LookupService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLookupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // admin is under `auth` middleware
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $type = (string) $this->input('type');

        $rules = [
            'type' => ['required', 'string', Rule::in(LookupService::types())],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
        ];

        if (LookupService::isTranslatable($type)) {
            // Translatable type: all three languages are required
            $rules['name'] = ['required', 'array'];
            $rules['name.uz'] = ['required', 'string', 'max:255'];
            $rules['name.ru'] = ['required', 'string', 'max:255'];
            $rules['name.kk'] = ['required', 'string', 'max:255'];
        } else {
            // Simple type: a single name
            $rules['name'] = ['required', 'string', 'max:255'];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'name' => __('Nom'),
            'name.uz' => __('Nomi (o‘zbekcha)'),
            'name.ru' => __('Nomi (ruscha)'),
            'name.kk' => __('Nomi (qoraqalpoqcha)'),
        ];
    }
}

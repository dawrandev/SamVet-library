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
        $type = (string) $this->input('type');

        $rules = [
            'type' => ['required', 'string', Rule::in(LookupService::types())],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
        ];

        if (LookupService::isTranslatable($type)) {
            // Tarjimali tur: uchala til ham majburiy
            $rules['name'] = ['required', 'array'];
            $rules['name.uz'] = ['required', 'string', 'max:255'];
            $rules['name.ru'] = ['required', 'string', 'max:255'];
            $rules['name.kk'] = ['required', 'string', 'max:255'];
        } else {
            // Oddiy tur: bitta nom
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

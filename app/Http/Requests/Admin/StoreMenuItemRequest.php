<?php

namespace App\Http\Requests\Admin;

use App\Enums\MenuItemType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreMenuItemRequest extends FormRequest
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
            'title.uz' => ['required', 'string', 'max:255'],
            'title.ru' => ['nullable', 'string', 'max:255'],
            'title.kk' => ['nullable', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:2048'],
            'type' => ['required', new Enum(MenuItemType::class)],
            'parent_id' => ['nullable', 'integer', 'exists:menu_items,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'target_blank' => ['boolean'],
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
            'url' => __('Havola'),
            'parent_id' => __('Ota menyu'),
            'sort_order' => __('Tartib'),
        ];
    }
}

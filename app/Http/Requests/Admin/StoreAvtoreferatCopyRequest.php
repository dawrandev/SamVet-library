<?php

namespace App\Http\Requests\Admin;

use App\Enums\CopyCondition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreAvtoreferatCopyRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route is under `auth` middleware. If roles are added — AvtoreferatCopyPolicy.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'inventory_number' => ['required', 'string', 'max:100', $this->inventoryNumberUniqueRule()],
            'condition' => ['nullable', 'array'],
            'condition.*' => [new Enum(CopyCondition::class)],
            'acquisition_act_number' => ['nullable', 'string', 'max:100'],
            'acquisition_act_at' => ['nullable', 'date'],
            'disposal_act_number' => ['nullable', 'string', 'max:100'],
            'disposal_act_at' => ['nullable', 'date'],
        ];
    }

    /**
     * Inventory number is unique (unrestricted on create).
     */
    protected function inventoryNumberUniqueRule(): object
    {
        return Rule::unique('avtoreferat_copies', 'inventory_number');
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'inventory_number' => __('Inventar raqami'),
            'condition' => __('Holati'),
            'acquisition_act_number' => __('Kirish akti raqami'),
            'acquisition_act_at' => __('Kirish akti sanasi'),
            'disposal_act_number' => __('Chiqish akti raqami'),
            'disposal_act_at' => __('Chiqish akti sanasi'),
        ];
    }
}

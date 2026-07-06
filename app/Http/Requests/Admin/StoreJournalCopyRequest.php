<?php

namespace App\Http\Requests\Admin;

use App\Enums\CopyCondition;
use App\Enums\CopyStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreJournalCopyRequest extends FormRequest
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
            'inventory_number' => ['required', 'string', 'max:100', $this->inventoryNumberUniqueRule()],
            'condition' => ['nullable', new Enum(CopyCondition::class)],
            'status' => ['required', new Enum(CopyStatus::class)],
            'location_id' => ['nullable', 'exists:locations,id'],
            'arrival_date' => ['nullable', 'date'],
            'price' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * Inventory number is unique (unrestricted on create).
     */
    protected function inventoryNumberUniqueRule(): object
    {
        return Rule::unique('journal_copies', 'inventory_number');
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'inventory_number' => __('Inventar raqami'),
            'condition' => __('Holati'),
            'status' => __('Mavjudligi'),
            'location_id' => __('Joylashuvi'),
            'arrival_date' => __('Kelgan vaqti'),
            'price' => __('Narxi'),
        ];
    }
}

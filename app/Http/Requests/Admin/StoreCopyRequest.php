<?php

namespace App\Http\Requests\Admin;

use App\Enums\BookFormat;
use App\Enums\CopyCondition;
use App\Enums\CopyStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreCopyRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Marshrut `auth` middleware ostida. Rollar qo'shilса — CopyPolicy.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'inventory_number' => ['required', 'string', 'max:100', $this->inventoryNumberUniqueRule()],
            'format' => ['required', new Enum(BookFormat::class)],
            'condition' => ['required', new Enum(CopyCondition::class)],
            'status' => ['required', new Enum(CopyStatus::class)],
            'location_id' => ['nullable', 'exists:locations,id'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'acquisition_act' => ['nullable', 'mimes:pdf', 'max:51200'], // 50 MB
            'disposal_act' => ['nullable', 'mimes:pdf', 'max:51200'],    // 50 MB
        ];
    }

    /**
     * Inventar raqami unikal (yaratishda cheklovsiz).
     */
    protected function inventoryNumberUniqueRule(): object
    {
        return Rule::unique('book_copies', 'inventory_number');
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'inventory_number' => __('Inventar raqami'),
            'format' => __('Formati'),
            'condition' => __('Holati'),
            'status' => __('Mavjudligi'),
            'location_id' => __('Joylashuvi'),
            'price' => __('Narxi'),
            'acquisition_act' => __('Kirish akti'),
            'disposal_act' => __('Chiqish akti'),
        ];
    }
}

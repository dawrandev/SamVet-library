<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route is under `auth` middleware. If roles are added — LoanPolicy.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'inventory_number' => ['required', 'string', 'max:100'],
            'due_at' => ['required', 'date', 'after_or_equal:today'],
            'note' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'inventory_number' => __('Inventar raqami'),
            'due_at' => __('Qaytarish muddati'),
            'note' => __('Izoh'),
        ];
    }

    /**
     * Custom messages — for cases where the generic translation is awkward.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // `after_or_equal:today` shows "today" instead of ":date" in the generic message — we make this case read naturally.
            'due_at.after_or_equal' => __('Qaytarish muddati bugundan oldingi sana bo‘lmasligi kerak.'),
        ];
    }
}

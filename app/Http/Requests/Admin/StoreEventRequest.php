<?php

namespace App\Http\Requests\Admin;

use App\Enums\EventRole;
use App\Enums\EventType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Marshrut `auth` middleware ostida. Rollar qo'shilsa — ReaderPolicy.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'name' => ['required', 'string', 'max:255'],
            'place' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_column(EventType::cases(), 'value'))],
            'role' => ['required', Rule::in(array_column(EventRole::cases(), 'value'))],
            'link' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'date' => __('Sanasi'),
            'name' => __('Nomi'),
            'place' => __('Joyi'),
            'type' => __('Turi'),
            'role' => __('Maqsadi'),
            'link' => __('Havola'),
            'note' => __('Izoh'),
        ];
    }
}

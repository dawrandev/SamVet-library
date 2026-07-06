<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreComputerSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route is under `auth` middleware. If roles are added — ReaderPolicy.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'issued_time' => ['nullable', 'date_format:H:i'],
            'returned_time' => ['nullable', 'date_format:H:i'],
            'computer_number' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'purpose' => ['nullable', 'string', 'max:255'],
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
            'issued_time' => __('Berilgan vaqti'),
            'returned_time' => __('Topshirish vaqti'),
            'computer_number' => __('Kompyuter raqami'),
            'location' => __('Joylashuv'),
            'purpose' => __('Maqsadi'),
            'note' => __('Izoh'),
        ];
    }
}

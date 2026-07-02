<?php

namespace App\Http\Requests\Admin;

use App\Enums\WarningReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWarningRequest extends FormRequest
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
            'reason' => ['required', Rule::in(array_column(WarningReason::cases(), 'value'))],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'reason' => __('Sababi'),
            'note' => __('Izoh'),
        ];
    }
}

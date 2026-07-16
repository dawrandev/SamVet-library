<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ExtendComputerSessionRequest extends FormRequest
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
            'minutes' => ['required', 'integer', 'min:1', 'max:1440'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'minutes' => __('Necha daqiqaga uzaytirish'),
        ];
    }
}

<?php

namespace App\Http\Requests\Site;

use Illuminate\Foundation\Http\FormRequest;

class ReaderLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * ID numbers are issued in upper case (e.g. BT0122001); accept any casing.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->id_number)) {
            $this->merge(['id_number' => strtoupper(trim($this->id_number))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id_number' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'id_number' => __('ID raqam'),
            'password' => __('Parol'),
        ];
    }
}

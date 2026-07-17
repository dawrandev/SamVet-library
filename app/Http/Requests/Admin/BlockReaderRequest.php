<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BlockReaderRequest extends FormRequest
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
        // If blocked_until is empty — permanent block.
        return [
            'blocked_until' => ['nullable', 'date', 'after:today'],
            'block_reason' => ['required', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'blocked_until' => __('Cheklov muddati'),
            'block_reason' => __('Bloklash sababi'),
        ];
    }
}

<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BlockReaderRequest extends FormRequest
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
        // blocked_until bo'sh bo'lsa — butunlay bloklash (permanent).
        return [
            'blocked_until' => ['nullable', 'date', 'after:today'],
            'block_reason' => ['nullable', 'string', 'max:1000'],
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

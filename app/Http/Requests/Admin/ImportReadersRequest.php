<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ImportReadersRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route is under `auth` middleware. When roles are added — ReaderPolicy.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls',
                'max:102400', // 100 MB (may be large due to images)
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'file' => __('Excel fayl'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => __('Excel faylni tanlang.'),
            'file.mimes' => __('Fayl formati .xlsx yoki .xls bo‘lishi kerak.'),
            'file.max' => __('Fayl hajmi 100 MB dan oshmasligi kerak.'),
        ];
    }
}

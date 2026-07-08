<?php

namespace App\Http\Requests\Admin;

use App\Enums\Month;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route is under `auth` middleware. If roles are added — Policy.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reader_id' => ['required', 'exists:readers,id'],
            'journal_id' => ['required', 'exists:journals,id'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'start_month' => ['required', 'integer', new Enum(Month::class)],
            'end_month' => ['required', 'integer', new Enum(Month::class), 'gte:start_month'],
            'amount' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'reader_id' => __('Obunachi'),
            'journal_id' => __('Nashr'),
            'year' => __('Yil'),
            'start_month' => __('Boshlanish oyi'),
            'end_month' => __('Tugash oyi'),
            'amount' => __('Summa'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reader_id.required' => __('Obunachini tanlang.'),
            'journal_id.required' => __('Nashrni tanlang.'),
            'end_month.gte' => __('Tugash oyi boshlanish oyidan oldin bo‘lmasligi kerak.'),
        ];
    }
}

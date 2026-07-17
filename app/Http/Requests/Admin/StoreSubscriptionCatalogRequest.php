<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubscriptionCatalogRequest extends FormRequest
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
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'journal_id' => [
                'required',
                'exists:journals,id',
                Rule::unique('subscription_catalogs')->where(fn ($query) => $query->where('year', $this->input('year')))
                    ->ignore($this->route('subscriptionCatalog')),
            ],
            'annual_price' => ['required', 'numeric', 'min:0'],
            'is_selected' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'year' => __('Yil'),
            'journal_id' => __('Nashr'),
            'annual_price' => __('Yillik summa'),
            'is_selected' => __('Bizga kerak'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'journal_id.unique' => __('Bu nashr shu yil katalogiga allaqachon qo‘shilgan.'),
        ];
    }
}

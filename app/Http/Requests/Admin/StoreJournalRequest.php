<?php

namespace App\Http\Requests\Admin;

use App\Enums\JournalPeriodicity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreJournalRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Marshrut `auth` middleware ostida. Rollar qo'shilsa — JournalPolicy.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'journal_type_id' => ['nullable', 'exists:journal_types,id'],
            'founder' => ['nullable', 'string', 'max:255'],
            'language_id' => ['nullable', 'exists:languages,id'],
            'publisher_id' => ['nullable', 'exists:publishers,id'],
            'issn' => ['nullable', 'string', 'max:30'],
            'index' => ['nullable', 'string', 'max:50'],
            'periodicity' => ['nullable', new Enum(JournalPeriodicity::class)],
            'publication_place' => ['nullable', 'array'],
            'publication_place.uz' => ['nullable', 'string', 'max:255'],
            'publication_place.ru' => ['nullable', 'string', 'max:255'],
            'publication_place.kk' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => __('Nomi'),
            'journal_type_id' => __('Turi'),
            'founder' => __('Muassis'),
            'language_id' => __('Tili'),
            'publisher_id' => __('Nashriyoti'),
            'issn' => __('ISSN'),
            'index' => __('Indeks'),
            'periodicity' => __('Davriyligi'),
        ];
    }
}

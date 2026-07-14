<?php

namespace App\Http\Requests\Admin;

use App\Enums\JournalPeriodicity;
use App\Enums\PublicationKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreJournalRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route is under `auth` middleware. If roles are added — JournalPolicy.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'kind' => ['required', new Enum(PublicationKind::class)],
            'journal_type_id' => ['nullable', 'exists:journal_types,id'],
            'founder' => ['nullable', 'string', 'max:255'],
            'language_id' => ['nullable', 'exists:languages,id'],
            'publisher' => ['nullable', 'array'],
            'publisher.uz' => ['nullable', 'string', 'max:255'],
            'publisher.ru' => ['nullable', 'string', 'max:255'],
            'publisher.kk' => ['nullable', 'string', 'max:255'],
            'publication_place_id' => ['nullable', 'exists:publication_places,id'],
            'issn' => ['nullable', 'string', 'max:30'],
            'index' => ['nullable', 'string', 'max:50'],
            'periodicity' => ['nullable', new Enum(JournalPeriodicity::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => __('Nomi'),
            'kind' => __('Turi (jurnal/gazeta)'),
            'journal_type_id' => __('Turi'),
            'founder' => __('Muassislar'),
            'language_id' => __('Tili'),
            'publisher' => __('Nashriyoti'),
            'publication_place_id' => __('Nashr joyi'),
            'issn' => __('ISSN'),
            'index' => __('Indeks'),
            'periodicity' => __('Davriyligi'),
        ];
    }
}

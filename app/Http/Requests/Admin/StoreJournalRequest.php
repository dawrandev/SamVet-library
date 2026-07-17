<?php

namespace App\Http\Requests\Admin;

use App\Enums\JournalPeriodicity;
use App\Enums\NewspaperType;
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
            'newspaper_type' => ['nullable', new Enum(NewspaperType::class)],
            'founder' => ['nullable', 'string', 'max:255'],
            'language_id' => ['nullable', 'exists:languages,id'],
            'publisher' => ['nullable', 'string', 'max:255'],
            'publication_place_id' => ['nullable', 'exists:publication_places,id'],
            'issn' => ['nullable', 'string', 'max:30'],
            'index' => ['nullable', 'string', 'max:50'],
            'periodicity' => ['nullable', new Enum(JournalPeriodicity::class)],
            'periodicity_count' => ['nullable', 'integer', 'min:1', 'max:31'],
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
            'newspaper_type' => __('Turi'),
            'founder' => __('Muassislar'),
            'language_id' => __('Tili'),
            'publisher' => __('Nashriyoti'),
            'publication_place_id' => __('Nashr joyi'),
            'issn' => __('ISSN'),
            'index' => __('Indeks'),
            'periodicity' => __('Davriylik birligi'),
            'periodicity_count' => __('Necha marta'),
        ];
    }
}

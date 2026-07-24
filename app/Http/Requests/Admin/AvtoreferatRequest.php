<?php

namespace App\Http\Requests\Admin;

use App\Enums\CopyCondition;
use App\Enums\DissertationDegree;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * Shared by store and update — the rules are identical.
 */
class AvtoreferatRequest extends FormRequest
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
        $maxYear = (int) date('Y') + 1;

        return [
            'title' => ['required', 'string', 'max:500'],
            'author' => ['nullable', 'string', 'max:500'],
            'specialty' => ['nullable', 'string', 'max:500'],
            'science_field_id' => ['nullable', 'integer', 'exists:science_fields,id'],
            'degree' => ['nullable', new Enum(DissertationDegree::class)],
            'council_number' => ['nullable', 'string', 'max:255'],
            'defense_institution' => ['nullable', 'string', 'max:500'],
            'performed_institution' => ['nullable', 'string', 'max:500'],
            'advisor' => ['required', 'string', 'max:500'],
            'udc' => ['nullable', 'string', 'max:50'],
            'registration_number' => ['nullable', 'string', 'max:255'],
            'condition' => ['nullable', new Enum(CopyCondition::class)],
            'publication_place_id' => ['nullable', 'integer', 'exists:publication_places,id'],
            'defense_year' => ['nullable', 'integer', 'min:1000', "max:{$maxYear}"],
            'inventory_number' => ['nullable', 'string', 'max:100'],
            'language_ids' => ['nullable', 'array'],
            'language_ids.*' => ['integer', 'exists:languages,id'],
            'electronic_file' => ['nullable', 'mimes:pdf', 'max:972800'], // 950 MB

            // Other participants (muharrir, tarjimon, ...) — a row is only kept when both fields are given.
            'contributors' => ['nullable', 'array'],
            'contributors.*.contributor_role_id' => ['required_with:contributors.*.name', 'integer', 'exists:contributor_roles,id'],
            'contributors.*.name' => ['required_with:contributors.*.contributor_role_id', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => __('Avtoreferat nomi'),
            'author' => __('Muallifi'),
            'specialty' => __('Ixtisoslik shifri va nomi'),
            'science_field_id' => __('Fan nomi'),
            'degree' => __('Turi'),
            'council_number' => __('Ilmiy kengash raqami'),
            'defense_institution' => __('Dissertatsiya himoya muassasi'),
            'performed_institution' => __('Dissertatsiya bajarilgan muassasi'),
            'advisor' => __('Ilmiy rahbar'),
            'udc' => __('UO‘K'),
            'registration_number' => __('Ro‘yxat raqami'),
            'condition' => __('Holati'),
            'publication_place_id' => __('Nashr joyi'),
            'defense_year' => __('Himoya yili'),
            'inventory_number' => __('Inventari'),
            'language_ids' => __('Tillari'),
            'electronic_file' => __('Elektron fayl'),
        ];
    }
}

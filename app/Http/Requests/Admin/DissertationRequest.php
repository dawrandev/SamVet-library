<?php

namespace App\Http\Requests\Admin;

use App\Enums\CopyCondition;
use App\Enums\DissertationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

/**
 * Shared by store and update — the rules are identical.
 */
class DissertationRequest extends FormRequest
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
            'degree' => ['nullable', new Enum(DissertationType::class)],
            'resource_field_id' => ['nullable', 'integer', 'exists:resource_fields,id'],
            'science_field_id' => ['nullable', 'integer', 'exists:science_fields,id'],
            'doctoral_specialty_id' => ['nullable', 'integer', 'exists:doctoral_specialties,id'],
            'master_specialty_id' => ['nullable', 'integer', 'exists:master_specialties,id'],
            'advisor' => ['nullable', 'string', 'max:500'],
            'institution' => ['nullable', 'string', 'max:500'],
            'language_id' => ['nullable', 'integer', 'exists:languages,id'],
            'publication_place_id' => ['nullable', 'integer', 'exists:publication_places,id'],
            'defense_year' => ['nullable', 'integer', 'min:1000', "max:{$maxYear}"],
            'pages' => ['nullable', 'integer', 'min:1'],
            'udc' => ['nullable', 'string', 'max:50'],
            'inventory_number' => ['nullable', 'string', 'max:100'],
            'condition' => ['nullable', new Enum(CopyCondition::class)],
            'annotation' => ['nullable', 'string'],
            'electronic_file' => ['nullable', 'mimes:pdf', 'max:972800'], // 950 MB

            // Other participants (muharrir, tarjimon, ...) — a row is only kept when both fields are given.
            'contributors' => ['nullable', 'array'],
            'contributors.*.contributor_role_id' => ['required_with:contributors.*.name', 'integer', 'exists:contributor_roles,id'],
            'contributors.*.name' => ['required_with:contributors.*.contributor_role_id', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $degree = $this->input('degree');

            // PhD/DSc dissertations need their science field + doctoral specialty;
            // Magistrlik ones need their master specialty. Only the relevant pair
            // is required — the form only shows the fields matching the chosen degree.
            if (in_array($degree, ['phd', 'dsc'], true)) {
                if (! $this->filled('science_field_id')) {
                    $validator->errors()->add('science_field_id', __('Fan nomini tanlang.'));
                }
                if (! $this->filled('doctoral_specialty_id')) {
                    $validator->errors()->add('doctoral_specialty_id', __('Ixtisoslikni tanlang.'));
                }
            } elseif ($degree === 'master' && ! $this->filled('master_specialty_id')) {
                $validator->errors()->add('master_specialty_id', __('Mutaxassislikni tanlang.'));
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => __('Dissertatsiya nomi'),
            'author' => __('Muallifi'),
            'degree' => __('Turi'),
            'resource_field_id' => __('Resurs sohasi'),
            'science_field_id' => __('Fan nomi'),
            'doctoral_specialty_id' => __('Ixtisoslik shifri va nomi'),
            'master_specialty_id' => __('Mutaxassislik shifri va nomi'),
            'advisor' => __('Ilmiy rahbari'),
            'institution' => __('Muassasi'),
            'language_id' => __('Tili'),
            'publication_place_id' => __('Nashr joyi'),
            'defense_year' => __('Himoya yili'),
            'pages' => __('Beti'),
            'udc' => __('UO‘K'),
            'inventory_number' => __('Inventari'),
            'condition' => __('Holati'),
            'annotation' => __('Annotatsiya'),
            'electronic_file' => __('Elektron fayl'),
        ];
    }
}

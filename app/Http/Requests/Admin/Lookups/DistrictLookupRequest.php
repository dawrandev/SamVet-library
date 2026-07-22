<?php

namespace App\Http\Requests\Admin\Lookups;

/**
 * Tuman — oddiy nom + ixtiyoriy viloyat (parent_id).
 */
class DistrictLookupRequest extends SimpleLookupRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'parent_id' => ['nullable', 'integer', 'exists:regions,id'],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'parent_id' => __('Viloyat'),
        ]);
    }
}

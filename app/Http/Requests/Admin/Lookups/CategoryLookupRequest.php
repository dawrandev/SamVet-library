<?php

namespace App\Http\Requests\Admin\Lookups;

/**
 * Kategoriya — tarjimali + ierarxik (parent_id ixtiyoriy).
 */
class CategoryLookupRequest extends TranslatableLookupRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'parent_id' => __('Ota kategoriya'),
        ]);
    }
}

<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

/**
 * Update validation — same rules as create.
 * Difference: the current member is skipped in the id_number uniqueness check.
 */
class UpdateReaderRequest extends StoreReaderRequest
{
    protected function idNumberUnique(): Unique
    {
        return Rule::unique('readers', 'id_number')->ignore($this->route('reader'));
    }
}

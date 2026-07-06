<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

/**
 * Copy update validation — same as create,
 * only the current copy is excluded from the inventory number uniqueness check.
 */
class UpdateCopyRequest extends StoreCopyRequest
{
    protected function inventoryNumberUniqueRule(): object
    {
        return Rule::unique('book_copies', 'inventory_number')
            ->ignore($this->route('copy'));
    }
}

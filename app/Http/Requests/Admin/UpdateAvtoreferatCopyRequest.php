<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

/**
 * Avtoreferat copy update validation — same as create,
 * only the current copy is excluded from the inventory number uniqueness check.
 */
class UpdateAvtoreferatCopyRequest extends StoreAvtoreferatCopyRequest
{
    protected function inventoryNumberUniqueRule(): object
    {
        return Rule::unique('avtoreferat_copies', 'inventory_number')
            ->ignore($this->route('copy'));
    }
}

<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

/**
 * Computer update validation — same as create,
 * only the current computer is excluded from the inventory/computer number uniqueness checks.
 */
class UpdateComputerRequest extends StoreComputerRequest
{
    protected function inventoryNumberUniqueRule(): object
    {
        return Rule::unique('computers', 'inventory_number')
            ->ignore($this->route('computer'));
    }

    protected function computerNumberUniqueRule(): object
    {
        return Rule::unique('computers', 'computer_number')
            ->ignore($this->route('computer'));
    }
}

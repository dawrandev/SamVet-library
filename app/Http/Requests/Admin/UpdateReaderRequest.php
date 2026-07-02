<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

/**
 * Tahrirlash validatsiyasi — yaratish bilan bir xil qoidalar.
 * Farqi: id_number unikallik tekshiruvida joriy a'zo o'tkazib yuboriladi.
 */
class UpdateReaderRequest extends StoreReaderRequest
{
    protected function idNumberUnique(): Unique
    {
        return Rule::unique('readers', 'id_number')->ignore($this->route('reader'));
    }
}

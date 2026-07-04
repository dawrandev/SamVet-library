<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

/**
 * Nusxani tahrirlash validatsiyasi — yaratish bilan bir xil,
 * faqat inventar raqami unikalligida joriy nusxa istisno qilinadi.
 */
class UpdateJournalCopyRequest extends StoreJournalCopyRequest
{
    protected function inventoryNumberUniqueRule(): object
    {
        return Rule::unique('journal_copies', 'inventory_number')
            ->ignore($this->route('copy'));
    }
}

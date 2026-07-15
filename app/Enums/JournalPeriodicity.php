<?php

namespace App\Enums;

/**
 * Journal periodicity unit — paired with a free "necha marta" count
 * (e.g. "Haftalik" + 3 = 3 marta haftada), since a fixed list of preset
 * combinations can't cover every real publication schedule.
 */
enum JournalPeriodicity: string
{
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';

    public function label(): string
    {
        return match ($this) {
            self::Weekly => __('Haftalik'),
            self::Monthly => __('Oylik'),
            self::Quarterly => __('Choraklik'),
        };
    }
}

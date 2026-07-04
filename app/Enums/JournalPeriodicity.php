<?php

namespace App\Enums;

/**
 * Jurnal davriyligi (nashr chastotasi).
 */
enum JournalPeriodicity: string
{
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case SemiAnnual = 'semiannual';
    case Annual = 'annual';

    public function label(): string
    {
        return match ($this) {
            self::Weekly => __('Haftalik'),
            self::Monthly => __('Oylik'),
            self::Quarterly => __('Choraklik'),
            self::SemiAnnual => __('Yarim yillik'),
            self::Annual => __('Yillik'),
        };
    }
}

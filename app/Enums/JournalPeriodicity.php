<?php

namespace App\Enums;

/**
 * Journal/newspaper periodicity — a fixed set of standard named frequencies
 * (the same idea libraries everywhere use, e.g. MARC21's frequency codes),
 * replacing an earlier free "unit + necha marta" pair. That pair could only
 * express "N times per unit" (e.g. 3 marta haftada) but not the inverse —
 * "once every N units" (masalan 2 oyda bir marta) — which real schedules
 * need too. A fixed list covers both directions without a raw number input.
 */
enum JournalPeriodicity: string
{
    case Daily = 'daily';                 // Kunlik
    case Semiweekly = 'semiweekly';       // Haftada 2 marta
    case Weekly = 'weekly';               // Haftalik
    case Biweekly = 'biweekly';           // 2 haftada bir
    case Semimonthly = 'semimonthly';     // Oyda 2 marta
    case Monthly = 'monthly';             // Oylik
    case Bimonthly = 'bimonthly';         // 2 oyda bir
    case Quarterly = 'quarterly';         // Choraklik (3 oyda bir)
    case Irregular = 'irregular';         // Muntazam emas

    public function label(): string
    {
        return match ($this) {
            self::Daily => __('Kunlik'),
            self::Semiweekly => __('Haftada 2 marta'),
            self::Weekly => __('Haftalik'),
            self::Biweekly => __('2 haftada bir'),
            self::Semimonthly => __('Oyda 2 marta'),
            self::Monthly => __('Oylik'),
            self::Bimonthly => __('2 oyda bir'),
            self::Quarterly => __('Choraklik'),
            self::Irregular => __('Muntazam emas'),
        };
    }
}

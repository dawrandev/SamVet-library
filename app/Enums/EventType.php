<?php

namespace App\Enums;

/**
 * Reader qatnashgan tadbir turi.
 */
enum EventType: string
{
    case Contest = 'contest';       // Tanlov
    case Event = 'event';           // Tadbir
    case Exhibition = 'exhibition'; // Ko'rgazma
    case Meeting = 'meeting';       // Uchrashuv

    public function label(): string
    {
        return match ($this) {
            self::Contest => __('Tanlov'),
            self::Event => __('Tadbir'),
            self::Exhibition => __('Ko‘rgazma'),
            self::Meeting => __('Uchrashuv'),
        };
    }
}

<?php

namespace App\Enums;

/**
 * Type of event the reader attended.
 */
enum EventType: string
{
    case Contest = 'contest';       // Contest
    case Event = 'event';           // Event
    case Exhibition = 'exhibition'; // Exhibition
    case Meeting = 'meeting';       // Meeting

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

<?php

namespace App\Enums;

/**
 * Member's library usage status.
 */
enum ReaderStatus: string
{
    case Active = 'active';        // active
    case Suspended = 'suspended';  // temporarily restricted (warnings)
    case Blocked = 'blocked';      // blocked
    case Left = 'left';            // left / graduated (Ketkenler)

    public function label(): string
    {
        return match ($this) {
            self::Active => __('Faol'),
            self::Suspended => __('Vaqtincha cheklangan'),
            self::Blocked => __('Bloklangan'),
            self::Left => __('Ketgan'),
        };
    }
}

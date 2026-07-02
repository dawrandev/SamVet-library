<?php

namespace App\Enums;

/**
 * A'zoning kutubxonadan foydalanish holati.
 */
enum ReaderStatus: string
{
    case Active = 'active';        // faol
    case Suspended = 'suspended';  // vaqtincha cheklangan (ogohlantirishlar)
    case Blocked = 'blocked';      // bloklangan
    case Left = 'left';            // ketgan / bitirgan (Ketkenler)

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

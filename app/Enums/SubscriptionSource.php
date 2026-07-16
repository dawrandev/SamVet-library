<?php

namespace App\Enums;

/**
 * Who funds a subscription — a closed set (either a specific reader pays
 * personally, or it's covered by the branch's own budget), not an
 * admin-extendable lookup table.
 */
enum SubscriptionSource: string
{
    case Reader = 'reader';
    case Budget = 'budget';

    public function label(): string
    {
        return match ($this) {
            self::Reader => __('Foydalanuvchidan'),
            self::Budget => __('Filial byudjetidan'),
        };
    }
}

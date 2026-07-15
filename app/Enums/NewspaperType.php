<?php

namespace App\Enums;

/**
 * Fixed newspaper (gazeta) type — unlike journals, newspapers only ever
 * fall into these two categories, so this is a closed enum instead of
 * the open, admin-extendable `journal_types` lookup table.
 */
enum NewspaperType: string
{
    case SpiritualEducational = 'spiritual_educational';
    case Pedagogical = 'pedagogical';

    public function label(): string
    {
        return match ($this) {
            self::SpiritualEducational => __('Ma’naviy-ma’rifiy gazeta'),
            self::Pedagogical => __('Pedagogik gazeta'),
        };
    }
}

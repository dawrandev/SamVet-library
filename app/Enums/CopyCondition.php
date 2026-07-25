<?php

namespace App\Enums;

/**
 * Physical condition of the copy.
 */
enum CopyCondition: string
{
    case New = 'new';
    case Old = 'old';
    case Torn = 'torn';
    case Repaired = 'repaired';
    case Scribbled = 'scribbled';
    case PagesIncomplete = 'pages_incomplete';

    public function label(): string
    {
        return match ($this) {
            self::New => __('Yangi'),
            self::Old => __('Eski'),
            self::Torn => __('Yirtilgan'),
            self::Repaired => __('Ta’mirlangan'),
            self::Scribbled => __('Sizilgan'),
            self::PagesIncomplete => __('Betlari to‘liq emas'),
        };
    }
}

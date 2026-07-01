<?php

namespace App\Enums;

/**
 * Nusxaning jismoniy holati.
 */
enum CopyCondition: string
{
    case New = 'new';
    case Old = 'old';
    case Torn = 'torn';
    case Repaired = 'repaired';
    case Scribbled = 'scribbled';

    public function label(): string
    {
        return match ($this) {
            self::New => __('Yangi'),
            self::Old => __('Eski'),
            self::Torn => __('Yirtilgan'),
            self::Repaired => __('Ta’mirlangan'),
            self::Scribbled => __('Sizilgan'),
        };
    }
}

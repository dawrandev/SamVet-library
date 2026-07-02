<?php

namespace App\Enums;

/**
 * Ogohlantirish sababi (qizil qoidalar bo'yicha).
 */
enum WarningReason: string
{
    case LostBook = 'lost_book';         // kitobni yo'qotdi
    case TornBook = 'torn_book';         // kitobni yirtdi
    case ScribbledBook = 'scribbled_book'; // kitobni chizdi/sizdi
    case Disorder = 'disorder';          // o'quv zalida tartibsizlik
    case Loud = 'loud';                  // baland ovozda gapirdi
    case Other = 'other';                // boshqa

    public function label(): string
    {
        return match ($this) {
            self::LostBook => __('Kitobni yo‘qotdi'),
            self::TornBook => __('Kitobni yirtdi'),
            self::ScribbledBook => __('Kitobni chizdi'),
            self::Disorder => __('O‘quv zalida tartibsizlik'),
            self::Loud => __('Baland ovozda gapirdi'),
            self::Other => __('Boshqa'),
        };
    }
}

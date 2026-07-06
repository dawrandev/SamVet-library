<?php

namespace App\Enums;

/**
 * Warning reason (per the red rules).
 */
enum WarningReason: string
{
    case LostBook = 'lost_book';         // lost the book
    case TornBook = 'torn_book';         // tore the book
    case ScribbledBook = 'scribbled_book'; // scribbled/marked the book
    case Disorder = 'disorder';          // disorderly conduct in the reading room
    case Loud = 'loud';                  // spoke loudly
    case Other = 'other';                // other

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

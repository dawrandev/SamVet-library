<?php

namespace App\Enums;

/**
 * Jismoniy nusxa formati (book_copies uchun).
 * Elektron/audio bular emas — ular books jadvalida fayl.
 */
enum BookFormat: string
{
    case Print = 'print';
    case Braille = 'braille';

    public function label(): string
    {
        return match ($this) {
            self::Print => __('Bosma'),
            self::Braille => __('Brayl'),
        };
    }
}

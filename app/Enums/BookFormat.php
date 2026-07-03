<?php

namespace App\Enums;

/**
 * Nusxa formati (book_copies uchun).
 * Elektron nusxa — katalog yozuvi; onlayn o'qish PDF'i alohida books.electronic_file'da.
 */
enum BookFormat: string
{
    case Print = 'print';
    case Electronic = 'electronic';
    case Braille = 'braille';

    public function label(): string
    {
        return match ($this) {
            self::Print => __('Bosma'),
            self::Electronic => __('Elektron'),
            self::Braille => __('Brayl'),
        };
    }
}

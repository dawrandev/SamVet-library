<?php

namespace App\Enums;

/**
 * Copy format (for book_copies).
 * Electronic copy is a catalog record; the online-reading PDF is stored separately in books.electronic_file.
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

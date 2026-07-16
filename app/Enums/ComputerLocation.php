<?php

namespace App\Enums;

/**
 * Fixed, closed set of computer locations in the reading room.
 * Unlike BookCopy/JournalCopy, computers don't use the open,
 * admin-extendable `locations` lookup table — there are only ever
 * these 3 physical spots.
 */
enum ComputerLocation: string
{
    case BookLending = 'book_lending';
    case ReadingHall = 'reading_hall';
    case ElectronicLibraryHall = 'electronic_library_hall';

    public function label(): string
    {
        return match ($this) {
            self::BookLending => __('Kitob berish bo‘limi'),
            self::ReadingHall => __('O‘qish zali'),
            self::ElectronicLibraryHall => __('Elektron kutubxona zali'),
        };
    }
}

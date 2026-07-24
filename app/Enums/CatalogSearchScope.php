<?php

namespace App\Enums;

/**
 * Which field the homepage/catalog search text is matched against — driven
 * by the hero search "scope chips" (Barchasi/Kitob nomi/Muallif/ISBN/Mavzu).
 */
enum CatalogSearchScope: string
{
    case All = 'all';
    case Title = 'title';
    case Author = 'author';
    case Isbn = 'isbn';
    case Topic = 'topic';

    public function label(): string
    {
        return match ($this) {
            self::All => __('Barchasi'),
            self::Title => __('Kitob nomi'),
            self::Author => __('Muallif'),
            self::Isbn => 'ISBN',
            self::Topic => __('Mavzu'),
        };
    }
}

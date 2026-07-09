<?php

namespace App\Enums;

use Illuminate\Database\Eloquent\Builder;

/**
 * Ordering options for the public catalog listing.
 * Keeps sort logic in one place instead of scattering magic strings.
 */
enum CatalogSort: string
{
    case Newest = 'newest';
    case Oldest = 'oldest';
    case Popular = 'popular';
    case Title = 'title';

    /** Human label shown in the sort dropdown (Uzbek — user-facing). */
    public function label(): string
    {
        return match ($this) {
            self::Newest => __('Yangi'),
            self::Oldest => __('Eski'),
            self::Popular => __('Ko‘p o‘qilgan'),
            self::Title => __('Nomi (A–Z)'),
        };
    }

    /** Apply this ordering to a book query. */
    public function apply(Builder $query): Builder
    {
        return match ($this) {
            self::Newest => $query->latest('id'),
            self::Oldest => $query->oldest('id'),
            self::Popular => $query->orderByDesc('views_count'),
            self::Title => $query->orderBy('title'),
        };
    }
}

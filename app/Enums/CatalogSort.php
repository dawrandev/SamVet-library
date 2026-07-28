<?php

namespace App\Enums;

use Illuminate\Support\Collection;

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

    /**
     * Order a merged, heterogeneous result set. Sorting happens in PHP, not
     * SQL — the catalog spans 5 tables, so ids aren't comparable across them
     * and "newest" keys off created_at instead, with a [type, id] tiebreak
     * so ordering stays deterministic across repeated requests.
     *
     * @param  Collection<int, array{type: string, id: int, title: string, views: int, created_at: ?\Illuminate\Support\Carbon}>  $rows
     * @return Collection<int, array{type: string, id: int, title: string, views: int, created_at: ?\Illuminate\Support\Carbon}>
     */
    public function sortRows(Collection $rows): Collection
    {
        $tieBreak = fn (array $r) => [$r['type'], $r['id']];

        return match ($this) {
            self::Newest => $rows->sortByDesc(fn (array $r) => [$r['created_at']?->getTimestamp() ?? 0, ...$tieBreak($r)])->values(),
            self::Oldest => $rows->sortBy(fn (array $r) => [$r['created_at']?->getTimestamp() ?? 0, ...$tieBreak($r)])->values(),
            self::Popular => $rows->sortByDesc(fn (array $r) => [$r['views'], $r['created_at']?->getTimestamp() ?? 0, ...$tieBreak($r)])->values(),
            self::Title => $rows->sortBy(fn (array $r) => mb_strtolower($r['title']), SORT_NATURAL)->values(),
        };
    }
}

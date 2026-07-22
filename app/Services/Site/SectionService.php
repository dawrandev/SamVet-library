<?php

namespace App\Services\Site;

use App\Enums\PublicationKind;
use App\Models\Audiobook;
use App\Models\Book;
use App\Models\BookType;
use App\Models\Journal;
use Illuminate\Support\Collection;

/**
 * The fund's sections (book types + periodicals) with their sizes and the page
 * each one opens. Shared by the home page and the sections page.
 */
class SectionService
{
    /**
     * @return Collection<int, array{key: string, label: string, count: int, url: string}>
     */
    public function tiles(): Collection
    {
        // One grouped query instead of a count per type.
        $countsByType = Book::query()
            ->selectRaw('book_type_id, COUNT(*) as c')
            ->groupBy('book_type_id')
            ->pluck('c', 'book_type_id');

        $locale = app()->getLocale();

        $tiles = BookType::query()
            ->orderBy('id')
            ->get(['id', 'name'])
            ->map(fn (BookType $type): array => [
                'key' => 'type-'.$type->id,
                'label' => $type->getTranslation('name', $locale, false) ?: $type->getTranslation('name', 'uz', false),
                'count' => (int) ($countsByType[$type->id] ?? 0),
                'url' => route('catalog', ['types' => [$type->id]]),
            ]);

        $periodicalCounts = Journal::query()
            ->selectRaw('kind, COUNT(*) as c')
            ->groupBy('kind')
            ->pluck('c', 'kind');

        return $tiles
            ->push([
                'key' => 'journals',
                'label' => __('Jurnallar'),
                'count' => (int) ($periodicalCounts[PublicationKind::Journal->value] ?? 0),
                'url' => route('periodicals.index', ['kind' => PublicationKind::Journal->value]),
            ])
            ->push([
                'key' => 'newspapers',
                'label' => __('Gazetalar'),
                'count' => (int) ($periodicalCounts[PublicationKind::Newspaper->value] ?? 0),
                'url' => route('periodicals.index', ['kind' => PublicationKind::Newspaper->value]),
            ])
            ->push([
                'key' => 'audiobooks',
                'label' => __('Audiokitoblar'),
                'count' => Audiobook::count(),
                'url' => route('audiobooks.index'),
            ])
            ->values();
    }
}

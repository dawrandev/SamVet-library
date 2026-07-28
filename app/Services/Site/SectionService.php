<?php

namespace App\Services\Site;

use App\Enums\PublicationKind;
use App\Models\Audiobook;
use App\Models\Avtoreferat;
use App\Models\Dissertation;
use App\Models\Journal;
use App\Models\Video;
use App\Repositories\Contracts\CatalogRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * The fund's sections (top-level categories + periodicals) with their sizes
 * and the page each one opens. Shared by the home page and the sections page.
 */
class SectionService
{
    public function __construct(
        private readonly CatalogRepositoryInterface $catalog,
    ) {}

    /**
     * @return Collection<int, array{key: string, label: string, count: int, url: string}>
     */
    public function tiles(): Collection
    {
        // Only top-level categories browse as their own section — a child
        // (e.g. "Iqtisodiyot nazariyasi") stays reachable via the catalog's
        // own category filter, not as a section tile of its own. The facet
        // count already rolls each parent's children up into it.
        $tiles = $this->catalog->categoryFacets()
            ->filter(fn (array $facet) => $facet['parentId'] === null)
            ->map(fn (array $facet): array => [
                'key' => 'category-'.$facet['id'],
                'label' => $facet['label'],
                'count' => $facet['count'],
                'url' => route('catalog', ['categories' => [$facet['id']]]),
            ])
            ->values();

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
            ->push([
                'key' => 'videos',
                'label' => __('Videolar'),
                'count' => Video::count(),
                'url' => route('videos.index'),
            ])
            ->push([
                'key' => 'dissertations',
                'label' => __('Dissertatsiyalar'),
                'count' => Dissertation::count(),
                'url' => route('dissertations.index'),
            ])
            ->push([
                'key' => 'avtoreferats',
                'label' => __('Avtoreferatlar'),
                'count' => Avtoreferat::count(),
                'url' => route('avtoreferats.index'),
            ])
            ->values();
    }
}

<?php

use App\Data\CatalogFilters;
use App\Models\Article;
use App\Models\Book;
use App\Repositories\Contracts\CatalogRepositoryInterface;

// These two tests genuinely need MySQL's FULLTEXT index to see committed
// rows (MATCH() AGAINST() is unreliable for rows only inserted, not
// committed, inside a transaction) — see tests/Pest.php for the
// DatabaseTruncation override that scopes this file specifically, and
// CatalogRepository::applySmartSearch() for the full explanation.

it('ranks a title match above a match that only appears in the annotation', function () {
    Book::factory()->create([
        'title' => 'Umumiy biologiya',
        'authors' => 'B. Odilov',
        'annotation' => 'Kitobda veterinariya sohasi haqida qisqacha eslatiladi.',
    ]);
    Book::factory()->create(['title' => 'Veterinariya asoslari', 'authors' => 'A. Karimov']);

    $filters = new CatalogFilters(search: 'veterinariya');
    $result = app(CatalogRepositoryInterface::class)->paginate($filters, 12);

    $titles = collect($result->items())->pluck('title')->all();
    expect(array_search('Veterinariya asoslari', $titles, true))
        ->toBeLessThan(array_search('Umumiy biologiya', $titles, true));
});

it('tolerates a truncated/prefix query via FULLTEXT boolean-mode wildcard', function () {
    Book::factory()->create(['title' => 'Veterinariya asoslari']);

    $filters = new CatalogFilters(search: 'veterinar');
    $result = app(CatalogRepositoryInterface::class)->paginate($filters, 12);

    expect($result->total())->toBeGreaterThan(0);
    expect(collect($result->items())->pluck('title'))->toContain('Veterinariya asoslari');
});

/**
 * Articles joined the catalog after the normalized search columns were added,
 * and that earlier migration deliberately skipped the FULLTEXT index on
 * `articles.search_text` — correctly, at the time, because articles were only
 * ever read through a leading-wildcard LIKE.
 *
 * The moment they entered the catalog, applySmartSearch() started putting
 * MATCH(search_text) AGAINST(...) in front of them. Without the index that is
 * not a slow query but a hard failure ("Can't find FULLTEXT index matching the
 * column list"), taking down the catalog page and the typeahead on any search.
 */
it('searches articles through FULLTEXT, which needs an index the migration adds', function () {
    Article::factory()->create(['title' => 'Veterinariya sohasidagi yangi tadqiqot']);
    Article::factory()->create(['title' => 'Butunlay boshqa mavzu']);

    $filters = new CatalogFilters(search: 'veterinariya');
    $result = app(CatalogRepositoryInterface::class)->paginate($filters, 12);

    $titles = collect($result->items())->pluck('title')->all();

    expect($titles)->toContain('Veterinariya sohasidagi yangi tadqiqot')
        ->and($titles)->not->toContain('Butunlay boshqa mavzu');
});

it('finds an article from the typeahead, which is the same MATCH() path', function () {
    Article::factory()->create(['title' => 'Gelmintlar va parazitlar']);

    $results = app(CatalogRepositoryInterface::class)->quickSearch('gelmintlar', 8);

    expect($results->pluck('title')->all())->toContain('Gelmintlar va parazitlar');
});

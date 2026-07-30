<?php

use App\Data\CatalogFilters;
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

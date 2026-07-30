<?php

use App\Data\CatalogFilters;
use App\Models\Audiobook;
use App\Models\Book;
use App\Repositories\Contracts\CatalogRepositoryInterface;
use App\Services\Site\SearchSuggestionService;

// Relevance-ranking and FULLTEXT-boolean-mode-wildcard tests live in
// SmartSearchFulltextTest.php instead (needs DatabaseTruncation — see
// tests/Pest.php). Everything here only depends on LIKE-fallback matching,
// stopword handling, or plain PHP logic, all of which work fine under the
// faster, transaction-rolled-back RefreshDatabase.

it('suggests a correction for a genuinely misspelled query that FULLTEXT prefix matching cannot catch', function () {
    Book::factory()->create(['title' => 'Veterinariya asoslari']);

    $suggestion = app(SearchSuggestionService::class)->suggest('veterenariya');

    expect($suggestion)->toBe('veterinariya');
});

it('does not suggest a correction when the search term is already a real word', function () {
    Book::factory()->create(['title' => 'Veterinariya asoslari']);

    expect(app(SearchSuggestionService::class)->suggest('veterinariya'))->toBeNull();
});

it('searches short (sub-3-letter) words via the LIKE fallback, word-boundary anchored', function () {
    Book::factory()->create(['title' => 'AI kitobi']);
    Book::factory()->create(['title' => 'Explicabo sit amet', 'authors' => 'Doe']);

    $filters = new CatalogFilters(search: 'AI');
    $result = app(CatalogRepositoryInterface::class)->paginate($filters, 12);

    $titles = collect($result->items())->pluck('title');
    expect($titles)->toContain('AI kitobi')
        ->not->toContain('Explicabo sit amet');
});

it('returns zero results (not the whole catalog) when the search term is entirely stopwords', function () {
    Book::factory()->count(3)->create();

    $filters = new CatalogFilters(search: 'va bilan');
    $result = app(CatalogRepositoryInterface::class)->paginate($filters, 12);

    expect($result->total())->toBe(0);
});

it('respects an explicit sort choice over relevance when both search and sort are given', function () {
    $older = Book::factory()->create(['title' => 'Veterinariya birinchi', 'created_at' => now()->subDays(5)]);
    $newer = Book::factory()->create(['title' => 'Veterinariya ikkinchi', 'created_at' => now()]);

    $filters = new CatalogFilters(search: 'veterinariya', sort: \App\Enums\CatalogSort::Oldest);
    $result = app(CatalogRepositoryInterface::class)->paginate($filters, 12);

    $ids = collect($result->items())->pluck('id')->all();
    expect(array_search($older->id, $ids, true))->toBeLessThan(array_search($newer->id, $ids, true));
});

it('quick-searches across resource types and returns the shared Resource shape', function () {
    Book::factory()->create(['title' => 'Veterinariya darsligi']);
    Audiobook::factory()->create(['name' => 'Veterinariya audio darsi']);

    $res = $this->getJson(route('catalog.quick-search', ['q' => 'veterinariya']));

    $res->assertOk();
    $data = $res->json('data');
    expect($data)->not->toBeEmpty();
    expect($data[0])->toHaveKeys(['type', 'type_label', 'title', 'author', 'cover_url', 'url']);
    expect(collect($data)->pluck('type'))->toContain('book')->toContain('audiobook');
});

it('quick-search returns an empty list for a blank or too-short query', function () {
    Book::factory()->create(['title' => 'Veterinariya darsligi']);

    $this->getJson(route('catalog.quick-search', ['q' => '']))->assertOk()->assertJson(['data' => []]);
});

it('auto-corrects a misspelled catalog search and shows the matching resource directly, not just a suggestion link', function () {
    Book::factory()->create(['title' => 'Veterinariya asoslari']);

    $res = $this->get(route('catalog', ['q' => 'veterenariya']));

    $res->assertOk()
        ->assertSee('Veterinariya asoslari') // the actual resource, not merely a "did you mean" link
        ->assertSee('veterenariya') // the corrected-term banner still names what was typed
        ->assertSee('veterinariya');
});

it('does not claim a correction when the suggested word still matches nothing under the active filters', function () {
    Book::factory()->create(['title' => 'Veterinariya asoslari']);
    $unusedType = \App\Models\BookType::factory()->create();

    $res = $this->get(route('catalog', ['q' => 'veterenariya', 'types' => [$unusedType->id]]));

    $res->assertOk()->assertSee('Hech narsa topilmadi');
});

it('finds the matching resource via quick-search typeahead even when the query is misspelled', function () {
    Book::factory()->create(['title' => 'Veterinariya asoslari']);

    $res = $this->getJson(route('catalog.quick-search', ['q' => 'veterenariya']));

    $res->assertOk();
    expect($res->json('data'))->not->toBeEmpty();
    expect(collect($res->json('data'))->pluck('title'))->toContain('Veterinariya asoslari');
});

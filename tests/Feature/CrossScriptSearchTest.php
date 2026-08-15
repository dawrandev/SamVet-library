<?php

use App\Data\CatalogFilters;
use App\Enums\CatalogSearchScope;
use App\Models\Book;
use App\Models\Dissertation;
use App\Repositories\Contracts\CatalogRepositoryInterface;
use App\Services\SearchIndexService;
use App\Services\Site\CatalogService;
use App\Services\Site\SearchSuggestionService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Script-independent catalog search: a reader typing Cyrillic must reach a
 * Latin-script record and vice versa, and a near-miss spelling must still
 * land on the resource they meant.
 */
beforeEach(function () {
    // The suggestion vocabulary is cached; each test seeds its own fund.
    Cache::forget('catalog.search_corpus');
});

function search(string $term): array
{
    $result = app(CatalogRepositoryInterface::class)->paginate(new CatalogFilters(search: $term), 12);

    return collect($result->items())->pluck('title')->all();
}

it('finds a Latin-script record from a Cyrillic query', function () {
    Book::factory()->create(['title' => 'Umumiy veterinariya asoslari']);

    expect(search('ветеринария'))->toContain('Umumiy veterinariya asoslari');
});

it('finds a Cyrillic-script record from a Latin query', function () {
    Book::factory()->create(['title' => 'Ветеринария']);

    expect(search('veterinariya'))->toContain('Ветеринария');
});

it('returns records of both scripts for one query, whichever script it is typed in', function () {
    Book::factory()->create(['title' => 'Umumiy veterinariya asoslari']);
    Book::factory()->create(['title' => 'Ветеринария']);

    expect(search('veterinariya'))->toHaveCount(2)
        ->and(search('ветеринария'))->toHaveCount(2)
        ->and(search('ВЕТЕРИНАРИЯ'))->toHaveCount(2);
});

it('matches a partial word across scripts, for the live typeahead', function () {
    Book::factory()->create(['title' => 'Ветеринария']);

    expect(search('veter'))->toContain('Ветеринария')
        ->and(search('вете'))->toContain('Ветеринария');
});

it('matches across scripts on the author field too', function () {
    Book::factory()->create(['title' => 'Iqtisodiyot nazariyasi', 'authors' => 'A. Oʻlmasov']);

    expect(search('Ўлмасов'))->toContain('Iqtisodiyot nazariyasi');
});

it('folds apostrophe variants so every spelling of the same title matches', function () {
    Book::factory()->create(['title' => 'Oʻzbekiston tarixi']);

    foreach (["o‘zbekiston", "o'zbekiston", 'ozbekiston', 'Ўзбекистон'] as $term) {
        expect(search($term))->toContain('Oʻzbekiston tarixi');
    }
});

it('applies the same folding to non-book resource types', function () {
    Dissertation::factory()->create(['title' => 'Ветеринария сохасида', 'author' => 'А. Ўлмасов']);

    expect(search('veterinariya'))->toContain('Ветеринария сохасида');
});

it('makes the Kitob nomi scope script-independent as well', function () {
    Book::factory()->create(['title' => 'Ветеринария', 'annotation' => 'Umumiy qoʻllanma']);

    $result = app(CatalogRepositoryInterface::class)->paginate(
        new CatalogFilters(search: 'veterinariya', scope: CatalogSearchScope::Title),
        12
    );

    expect(collect($result->items())->pluck('title'))->toContain('Ветеринария');
});

it('ranks a record matching every query word above one matching only some', function () {
    Book::factory()->create(['title' => 'Ветеринария']);
    Book::factory()->create(['title' => 'Umumiy veterinariya asoslari']);

    expect(search('umumiy veterinariya')[0])->toBe('Umumiy veterinariya asoslari')
        ->and(search('умумий ветеринария')[0])->toBe('Umumiy veterinariya asoslari');
});

it('does not throw when a search actually matches (MySQL error 1690 regression)', function () {
    // A MATCH() in the SELECT list next to an OR in the WHERE used to make
    // MySQL 8 return a garbage rank that overflowed on the first arithmetic
    // operation — a hard 500 on every search that found anything.
    Book::factory()->create(['title' => 'Umumiy veterinariya asoslari']);

    expect(fn () => search('veterinariya'))->not->toThrow(Exception::class)
        ->and(search('veterinariya'))->toHaveCount(1);
});

it('does not let a short query match inside an unrelated word', function () {
    // Regression: matching the whole phrase as a bare '%ai%' across the
    // record made a two-letter query hit any word merely containing those
    // letters. SmartSearchTest covers the same contract, but only trips over
    // it when random factory text happens to contain such a word — this
    // states it outright.
    Book::factory()->create(['title' => 'AI kitobi']);
    Book::factory()->create(['title' => 'Maiores quia', 'authors' => 'Doe', 'annotation' => 'Aliquid maiores']);

    expect(search('AI'))->toContain('AI kitobi')->not->toContain('Maiores quia');
});

it('still matches an inner fragment of a long word', function () {
    // The length gate above must not cost the substring tolerance that makes
    // "terinar" find "veterinariya".
    Book::factory()->create(['title' => 'Umumiy veterinariya asoslari']);

    expect(search('terinar'))->toContain('Umumiy veterinariya asoslari');
});

it('corrects a misspelling typed in Cyrillic', function () {
    Book::factory()->create(['title' => 'Veterinariya asoslari']);

    // "ветеренария" normalizes to "veterenariya" — one edit from the indexed word.
    expect(app(SearchSuggestionService::class)->suggest('ветеренария'))->toBe('veterinariya');
});

it('only suggests words that are genuinely in the catalog', function () {
    Book::factory()->create(['title' => 'Veterinariya asoslari']);

    expect(app(SearchSuggestionService::class)->suggest('zzzzzzqqqq'))->toBeNull();
});

it('will not turn a short word into an unrelated one', function () {
    Book::factory()->create(['title' => 'Kitob haqida']);

    // "olma" -> "kitob" is far too big a leap for a four-letter word.
    expect(app(SearchSuggestionService::class)->suggest('olma'))->toBeNull();
});

it('corrects a typo even when another word in the query still matches something', function () {
    Book::factory()->create(['title' => 'Veterinariya asoslari']);
    Book::factory()->count(3)->create(['title' => 'Fizika asoslari']);

    // "asoslari" alone already matches, so the old zero-results-only trigger
    // never fired and the visitor was left with the wrong books.
    $data = app(CatalogService::class)->catalogData(new CatalogFilters(search: 'vetrenariya asoslari'));

    expect($data['correctedSearch'])->toBe('veterinariya asoslari')
        ->and(collect($data['items']->items())->pluck('title'))->toContain('Veterinariya asoslari');
});

it('keeps the visitor own results when a correction would not find more', function () {
    Book::factory()->create(['title' => 'Veterinariya asoslari']);

    $data = app(CatalogService::class)->catalogData(new CatalogFilters(search: 'veterinariya'));

    expect($data['correctedSearch'])->toBeNull()
        ->and($data['total'])->toBe(1);
});

it('fills the normalized columns whenever a record is saved', function () {
    $book = Book::factory()->create(['title' => 'Ветеринария', 'authors' => 'А. Ўлмасов']);

    expect($book->fresh()->title_normalized)->toBe('veterinariya')
        ->and($book->fresh()->search_text)->toContain('a olmasov');

    $book->update(['title' => 'Иқтисодиёт']);

    expect($book->fresh()->title_normalized)->toBe('iqtisodiyot');
});

it('backfills the normalized columns for rows written without them', function () {
    $book = Book::factory()->create(['title' => 'Ветеринария']);

    // Straight to the DB: the derived columns are deliberately not in
    // Book::$fillable (nothing outside SearchIndexService may set them), so
    // this is the only way to simulate a row that predates the migration.
    DB::table('books')->where('id', $book->id)
        ->update(['search_text' => null, 'title_normalized' => null]);

    expect($book->fresh()->search_text)->toBeNull();

    app(SearchIndexService::class)->reindex();

    expect($book->fresh()->title_normalized)->toBe('veterinariya');
});

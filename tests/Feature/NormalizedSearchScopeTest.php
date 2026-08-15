<?php

use App\Models\Article;
use App\Models\Audiobook;
use App\Models\Avtoreferat;
use App\Models\Book;
use App\Models\Dissertation;
use App\Models\Journal;
use App\Models\Reader;
use App\Models\Video;
use App\Data\CatalogFilters;
use App\Repositories\Contracts\ArticleRepositoryInterface;
use App\Repositories\Contracts\CatalogRepositoryInterface;
use App\Repositories\Contracts\AudiobookRepositoryInterface;
use App\Repositories\Contracts\AvtoreferatRepositoryInterface;
use App\Repositories\Contracts\BookRepositoryInterface;
use App\Repositories\Contracts\DissertationRepositoryInterface;
use App\Repositories\Contracts\JournalRepositoryInterface;
use App\Repositories\Contracts\ReaderRepositoryInterface;
use App\Repositories\Contracts\VideoRepositoryInterface;
use App\Services\Site\SearchSuggestionService;
use Illuminate\Support\Facades\Cache;

/**
 * Script-independent search outside the unified public catalog: the per-type
 * public listing pages, the periodical (journal/article) tables, and the
 * librarian-only reader search.
 */
beforeEach(function () {
    Cache::forget('catalog.search_corpus');
});

// --- Public per-type listing pages -------------------------------------

it('searches the public audiobook list across scripts', function () {
    Audiobook::factory()->create(['name' => 'Ветеринария', 'author' => 'А. Ўлмасов']);

    $found = app(AudiobookRepositoryInterface::class)->filtered(['search' => 'veterinariya'])->get();

    expect($found)->toHaveCount(1);
});

it('searches the public video list across scripts', function () {
    Video::factory()->create(['name' => 'Ветеринария', 'author' => 'А. Ўлмасов']);

    expect(app(VideoRepositoryInterface::class)->filtered(['search' => 'Ўлмасов'])->get())->toHaveCount(1)
        ->and(app(VideoRepositoryInterface::class)->filtered(['search' => 'olmasov'])->get())->toHaveCount(1);
});

it('searches the public dissertation list across scripts', function () {
    Dissertation::factory()->create(['title' => 'Ветеринария сохасида', 'author' => 'А. Ўлмасов']);

    expect(app(DissertationRepositoryInterface::class)->filtered(['search' => 'veterinariya'])->get())->toHaveCount(1);
});

it('searches the public avtoreferat list across scripts', function () {
    Avtoreferat::factory()->create(['title' => 'Ветеринария сохасида', 'author' => 'А. Ўлмасов']);

    expect(app(AvtoreferatRepositoryInterface::class)->filtered(['search' => 'veterinariya'])->get())->toHaveCount(1);
});

it('returns nothing rather than the whole table for a punctuation-only term', function () {
    Video::factory()->count(3)->create();

    expect(app(VideoRepositoryInterface::class)->filtered(['search' => '%%%'])->get())->toHaveCount(0);
});

it('treats a stray percent sign as a literal, not a wildcard', function () {
    Book::factory()->create(['title' => 'Veterinariya asoslari', 'isbn' => '978-9943-01-234-5']);

    // '%' reaches the raw ISBN clause; unescaped it would match every row.
    expect(app(BookRepositoryInterface::class)->filtered(['search' => '%'])->get())->toHaveCount(0);
});

// --- Journals and articles ---------------------------------------------

it('searches journals across scripts', function () {
    Journal::factory()->create(['name' => 'Ветеринария ахборотномаси']);

    expect(app(JournalRepositoryInterface::class)->filtered(['search' => 'veterinariya'])->get())->toHaveCount(1)
        ->and(app(JournalRepositoryInterface::class)->filtered(['search' => 'ахборотномаси'])->get())->toHaveCount(1);
});

it('searches articles across scripts', function () {
    Article::factory()->create(['title' => 'Ветеринария масалалари', 'author' => 'А. Ўлмасов']);

    expect(app(ArticleRepositoryInterface::class)->paginate(['search' => 'veterinariya'])->total())->toBe(1)
        ->and(app(ArticleRepositoryInterface::class)->paginate(['search' => 'Ўлмасов'])->total())->toBe(1);
});

it('now also searches an article annotation, which the old title+author search missed', function () {
    Article::factory()->create(['title' => 'Boshqa mavzu', 'author' => 'B. Xodiyev', 'annotation' => 'Ветеринария бўйича тадқиқот']);

    expect(app(ArticleRepositoryInterface::class)->paginate(['search' => 'veterinariya'])->total())->toBe(1);
});

it('adds journal and article words to the public did-you-mean vocabulary', function () {
    Journal::factory()->create(['name' => 'Ахборотнома']);

    // "ахборотномо" -> normalized "axborotnomo", one edit from "axborotnoma".
    expect(app(SearchSuggestionService::class)->suggest('axborotnomo'))->toBe('axborotnoma');
});

// --- Readers (librarian only) ------------------------------------------

it('searches readers across scripts', function () {
    Reader::factory()->create(['full_name' => 'Ўлмасов Азиз']);

    expect(app(ReaderRepositoryInterface::class)->filtered(['search' => 'olmasov'])->get())->toHaveCount(1)
        ->and(app(ReaderRepositoryInterface::class)->filtered(['search' => 'Ўлмасов'])->get())->toHaveCount(1);
});

it('still finds a reader by ID number', function () {
    $reader = Reader::factory()->create(['full_name' => 'Test Reader']);

    expect(app(ReaderRepositoryInterface::class)->filtered(['search' => $reader->id_number])->get())
        ->toHaveCount(1);
});

it('never leaks reader names into the public suggestion vocabulary', function () {
    // The suggestion corpus is reachable from an unauthenticated endpoint, so
    // borrower names must not be probeable through spelling correction.
    Reader::factory()->create(['full_name' => 'Zulfiqorov Bekzod']);
    Book::factory()->create(['title' => 'Veterinariya asoslari']);

    expect(app(SearchSuggestionService::class)->suggest('zulfiqorow'))->toBeNull()
        ->and(app(SearchSuggestionService::class)->suggest('bekzot'))->toBeNull();
});

// --- Admin book search -------------------------------------------------

it('searches admin books across scripts while keeping ISBN matching', function () {
    Book::factory()->create(['title' => 'Ветеринария асослари', 'isbn' => '978-9943-01-234-5']);

    expect(app(BookRepositoryInterface::class)->filtered(['search' => 'veterinariya'])->get())->toHaveCount(1)
        ->and(app(BookRepositoryInterface::class)->filtered(['search' => '978-9943-01-234-5'])->get())->toHaveCount(1);
});

it('keeps ISBN digits out of the public catalog blob so a year cannot collide with one', function () {
    // Admin ORs `isbn` in deliberately, so a librarian typing a fragment of
    // one still finds the book. The public catalog does not — it matches
    // search_text alone, which is why the ISBN is kept out of it.
    Book::factory()->create(['title' => 'Fizika', 'authors' => 'B. Xodiyev', 'isbn' => '978-2019-01-234-5', 'annotation' => 'Oddiy']);

    $public = app(CatalogRepositoryInterface::class)->paginate(new CatalogFilters(search: '2019'), 12);

    expect($public->total())->toBe(0)
        ->and(app(BookRepositoryInterface::class)->filtered(['search' => '978-2019'])->get())->toHaveCount(1);
});

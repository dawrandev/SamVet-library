<?php

use App\Enums\BookFormat;
use App\Enums\CatalogResourceType;
use App\Models\Audiobook;
use App\Models\Avtoreferat;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Category;
use App\Models\Dissertation;
use App\Models\Video;
use Illuminate\Support\Facades\DB;

it('shows both Bosma and Elektron on a card when a book has a print copy and an online-readable PDF, with no electronic copy row', function () {
    $book = Book::factory()->withPdf()->create();
    BookCopy::factory()->create(['book_id' => $book->id, 'format' => 'print']);

    $res = $this->get(route('catalog'));

    $item = $res->viewData('items')->getCollection()->first();
    expect($item->formats)->toContain(BookFormat::Print, BookFormat::Electronic);
});

it('matches Shakli=Elektron for a book with only electronic_file and no electronic copy row', function () {
    $book = Book::factory()->withPdf()->create();
    BookCopy::factory()->create(['book_id' => $book->id, 'format' => 'print']);
    Book::factory()->create(); // print-only, no PDF — must not match

    $res = $this->get(route('catalog', ['formats' => ['electronic']]));

    expect($res->viewData('total'))->toBe(1);
});

it('counts a book with only electronic_file (no electronic copy row) in the Elektron facet', function () {
    Book::factory()->withPdf()->create();

    $res = $this->get(route('catalog'));

    $facet = collect($res->viewData('formats'))->firstWhere('id', 'electronic');
    expect($facet['count'])->toBe(1);
});

it('shows "not available" instead of "0 nusxa mavjud" when a book has zero available copies', function () {
    $book = Book::factory()->create();
    BookCopy::factory()->borrowed()->create(['book_id' => $book->id, 'format' => 'print']);
    actingAsReader();

    $res = $this->get(route('catalog'));

    $res->assertSee(__('Hozircha ARMda mavjud emas'))
        ->assertDontSee('0 '.__('nusxa mavjud'), false);
});

it('still shows the green "N nusxa mavjud" pill when copies are available', function () {
    $book = Book::factory()->create();
    BookCopy::factory()->create(['book_id' => $book->id, 'format' => 'print']);
    actingAsReader();

    $this->get(route('catalog'))->assertSee(__('ARMda :n nusxa mavjud', ['n' => 1]));
});

it('includes one of each of the 5 resource types by default, each linking to its own show route', function () {
    $book = Book::factory()->create();
    $audiobook = Audiobook::factory()->create();
    $video = Video::factory()->create();
    $dissertation = Dissertation::factory()->create();
    $avtoreferat = Avtoreferat::factory()->create();

    $res = $this->get(route('catalog'));

    $res->assertOk();
    expect($res->viewData('total'))->toBe(5);

    $items = $res->viewData('items')->getCollection();
    $slugs = $items->pluck('slug')->all();

    expect($slugs)->toContain($book->slug, $audiobook->slug, $video->slug, $dissertation->slug, $avtoreferat->slug);

    $bookItem = $items->firstWhere('slug', $book->slug);
    expect($bookItem->url())->toBe(route('book.show', $book->slug));

    $audiobookItem = $items->firstWhere('slug', $audiobook->slug);
    expect($audiobookItem->url())->toBe(route('audiobook.show', $audiobook->slug))
        ->and($audiobookItem->badgeLabel())->toBe(__('Audiokitob'));

    $videoItem = $items->firstWhere('slug', $video->slug);
    expect($videoItem->url())->toBe(route('video.show', $video->slug))
        ->and($videoItem->badgeLabel())->toBe(__('Video'));

    $dissertationItem = $items->firstWhere('slug', $dissertation->slug);
    expect($dissertationItem->url())->toBe(route('dissertation.show', $dissertation->slug))
        ->and($dissertationItem->badgeLabel())->toBe(__('Dissertatsiya'));

    $avtoreferatItem = $items->firstWhere('slug', $avtoreferat->slug);
    expect($avtoreferatItem->url())->toBe(route('avtoreferat.show', $avtoreferat->slug))
        ->and($avtoreferatItem->badgeLabel())->toBe(__('Avtoreferat'));
});

it('isolates to audiobooks only when Shakli=audio', function () {
    Book::factory()->create();
    Audiobook::factory()->count(2)->create();
    Video::factory()->create();

    $res = $this->get(route('catalog', ['formats' => ['audio']]));

    expect($res->viewData('total'))->toBe(2);
    expect($res->viewData('items')->getCollection()->every(fn ($i) => $i->type === CatalogResourceType::Audiobook))->toBeTrue();
});

it('isolates to videos only when Shakli=video', function () {
    Book::factory()->create();
    Audiobook::factory()->create();
    Video::factory()->count(2)->create();

    $res = $this->get(route('catalog', ['formats' => ['video']]));

    expect($res->viewData('total'))->toBe(2);
    expect($res->viewData('items')->getCollection()->every(fn ($i) => $i->type === CatalogResourceType::Video))->toBeTrue();
});

it('isolates to print books only when Shakli=print', function () {
    $printBook = Book::factory()->create();
    BookCopy::factory()->create(['book_id' => $printBook->id, 'format' => 'print']);
    $electronicBook = Book::factory()->create();
    BookCopy::factory()->create(['book_id' => $electronicBook->id, 'format' => 'electronic']);
    Audiobook::factory()->create();

    $res = $this->get(route('catalog', ['formats' => ['print']]));

    expect($res->viewData('total'))->toBe(1);
});

it('isolates to braille books only when Shakli=braille', function () {
    $brailleBook = Book::factory()->create();
    BookCopy::factory()->create(['book_id' => $brailleBook->id, 'format' => 'braille']);
    Book::factory()->create();
    Video::factory()->create();

    $res = $this->get(route('catalog', ['formats' => ['braille']]));

    expect($res->viewData('total'))->toBe(1);
});

it('folds dissertations and avtoreferats into Shakli=electronic alongside electronic-copy books', function () {
    $electronicBook = Book::factory()->create();
    BookCopy::factory()->create(['book_id' => $electronicBook->id, 'format' => 'electronic']);
    $printBook = Book::factory()->create();
    BookCopy::factory()->create(['book_id' => $printBook->id, 'format' => 'print']);
    Dissertation::factory()->count(2)->create();
    Avtoreferat::factory()->create();
    Audiobook::factory()->create();

    $res = $this->get(route('catalog', ['formats' => ['electronic']]));

    // electronicBook + 2 dissertations + 1 avtoreferat = 4; excludes printBook and audiobook.
    expect($res->viewData('total'))->toBe(4);
});

it('combines multiple Shakli options additively', function () {
    Audiobook::factory()->count(2)->create();
    Video::factory()->count(3)->create();
    Book::factory()->create();

    $res = $this->get(route('catalog', ['formats' => ['audio', 'video']]));

    expect($res->viewData('total'))->toBe(5);
});

it('narrows the whole catalog to books when a book-only facet is active, dropping other types', function () {
    $category = Category::factory()->create();
    $book = Book::factory()->create();
    $book->categories()->attach($category->id);
    Book::factory()->create(); // uncategorized book, must not match
    Audiobook::factory()->create();
    Video::factory()->create();

    $res = $this->get(route('catalog', ['categories' => [$category->id]]));

    expect($res->viewData('total'))->toBe(1);
});

it('returns an empty result set when a Shakli=audio filter is combined with a book-only facet', function () {
    $category = Category::factory()->create();
    $book = Book::factory()->create();
    $book->categories()->attach($category->id);
    Audiobook::factory()->count(3)->create();

    $res = $this->get(route('catalog', ['formats' => ['audio'], 'categories' => [$category->id]]));

    $res->assertOk();
    expect($res->viewData('total'))->toBe(0);
});

it('searches across all 5 types by title/name on the default scope', function () {
    Book::factory()->create(['title' => 'Veterinariya asoslari']);
    Audiobook::factory()->create(['name' => 'Veterinariya darsligi']);
    Video::factory()->create(['name' => 'Boshqa mavzu']);
    Dissertation::factory()->create(['title' => 'Veterinariya tadqiqoti']);
    Avtoreferat::factory()->create(['title' => 'Boshqa ish']);

    $res = $this->get(route('catalog', ['q' => 'veterinariya']));

    expect($res->viewData('total'))->toBe(3);
});

it('scopes q to the title/name column only across all types when scope=title', function () {
    Audiobook::factory()->create(['name' => 'Veterinariya darsligi', 'author' => 'Boshqa muallif']);
    Audiobook::factory()->create(['name' => 'Boshqa nom', 'author' => 'Veterinariya Aliyev']);

    $res = $this->get(route('catalog', ['q' => 'veterinariya', 'scope' => 'title']));

    expect($res->viewData('total'))->toBe(1);
});

it('scopes q to the annotation only across non-book types when scope=topic', function () {
    Video::factory()->create(['name' => 'Birinchi video', 'annotation' => 'Chorvachilik mavzusida.']);
    Video::factory()->create(['name' => 'Ikkinchi video', 'annotation' => 'Boshqa mavzu.']);

    $res = $this->get(route('catalog', ['q' => 'chorvachilik', 'scope' => 'topic']));

    expect($res->viewData('total'))->toBe(1);
});

it('forces Book-only when scope=isbn even though other types would otherwise match the search text', function () {
    Book::factory()->create(['isbn' => '9781112223334']);
    Audiobook::factory()->create(['name' => '9781112223334 nomli audiokitob']);

    $res = $this->get(route('catalog', ['q' => '9781112223334', 'scope' => 'isbn']));

    expect($res->viewData('total'))->toBe(1);
});

it('matches the standalone author filter across every type\'s own author column', function () {
    Book::factory()->create(['authors' => 'A. O‘lmasov']);
    Audiobook::factory()->create(['author' => 'B. O‘lmasov']);
    Video::factory()->create(['author' => 'Boshqa kishi']);
    Dissertation::factory()->create(['author' => 'C. O‘lmasov']);

    $res = $this->get(route('catalog', ['author' => 'O‘lmasov']));

    expect($res->viewData('total'))->toBe(3);
});

it('sorts a mixed set by popularity (views_count) across types', function () {
    $low = Book::factory()->create(['views_count' => 1]);
    $high = Audiobook::factory()->create(['views_count' => 100]);
    $mid = Video::factory()->create(['views_count' => 50]);

    $res = $this->get(route('catalog', ['sort' => 'popular']));

    $slugs = $res->viewData('items')->getCollection()->pluck('slug')->all();
    expect($slugs)->toBe([$high->slug, $mid->slug, $low->slug]);
});

it('sorts a mixed set by title across types', function () {
    Video::factory()->create(['name' => 'Zebra']);
    Book::factory()->create(['title' => 'Alpha']);
    Audiobook::factory()->create(['name' => 'Mango']);

    $res = $this->get(route('catalog', ['sort' => 'title']));

    $titles = $res->viewData('items')->getCollection()->pluck('title')->all();
    expect($titles)->toBe(['Alpha', 'Mango', 'Zebra']);
});

it('paginates correctly across merged types and preserves active filters + path in the links', function () {
    Book::factory()->count(7)->create();
    Audiobook::factory()->count(7)->create();

    $res = $this->get(route('catalog', ['sort' => 'title']));

    expect($res->viewData('total'))->toBe(14);
    expect(count($res->viewData('items')))->toBe(12);

    $html = $res->getContent();
    // Pagination links must point at /katalog (the explicit `path` option),
    // never "/", and must preserve the active sort in the query string.
    expect($html)->toContain('/katalog?')
        ->and($html)->toContain('sort=title')
        ->not->toContain('href="/?');

    $page2 = $this->get(route('catalog', ['sort' => 'title', 'page' => 2]));
    expect(count($page2->viewData('items')))->toBe(2);
});

it('returns 200 with an empty grid for an out-of-range page instead of erroring', function () {
    Book::factory()->count(2)->create();

    $res = $this->get(route('catalog', ['page' => 99]));

    $res->assertOk();
    expect($res->viewData('items'))->toBeEmpty();
});

it('reports correct formatFacets() counts including the Electronic fold-in math', function () {
    $printBook = Book::factory()->create();
    BookCopy::factory()->create(['book_id' => $printBook->id, 'format' => 'print']);
    $electronicBook = Book::factory()->create();
    BookCopy::factory()->create(['book_id' => $electronicBook->id, 'format' => 'electronic']);
    Dissertation::factory()->count(2)->create();
    Avtoreferat::factory()->create();
    Audiobook::factory()->count(3)->create();
    Video::factory()->count(4)->create();

    $res = $this->get(route('catalog'));
    $facets = collect($res->viewData('formats'))->keyBy('id');

    expect($facets['print']['count'])->toBe(1)
        ->and($facets['electronic']['count'])->toBe(4) // 1 electronic book + 2 dissertations + 1 avtoreferat
        ->and($facets['audio']['count'])->toBe(3)
        ->and($facets['video']['count'])->toBe(4);
});

it('keeps the query count low regardless of how many rows exist per type (two-phase design guard)', function () {
    Book::factory()->count(15)->create();
    Audiobook::factory()->count(15)->create();
    Video::factory()->count(15)->create();
    Dissertation::factory()->count(15)->create();
    Avtoreferat::factory()->count(15)->create();

    DB::enableQueryLog();
    $this->get(route('catalog'))->assertOk();
    $queryCount = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($queryCount)->toBeLessThan(40);
});

it('mixes books, dissertations and avtoreferats tagged with the same category in one filtered result', function () {
    $category = Category::factory()->create();
    $book = Book::factory()->create();
    $book->categories()->attach($category->id);
    $dissertation = Dissertation::factory()->create(['category_id' => $category->id]);
    $avtoreferat = Avtoreferat::factory()->create(['category_id' => $category->id]);
    Audiobook::factory()->create(); // no category concept at all — must not match
    Book::factory()->create(); // untagged — must not match

    $res = $this->get(route('catalog', ['categories' => [$category->id]]));

    expect($res->viewData('total'))->toBe(3);
});

it('excludes audiobooks and videos from a category-filtered result — neither carries a category', function () {
    $category = Category::factory()->create();
    Dissertation::factory()->create(['category_id' => $category->id]);
    Audiobook::factory()->create();
    Video::factory()->create();

    $res = $this->get(route('catalog', ['categories' => [$category->id]]));

    $types = $res->viewData('items')->getCollection()->pluck('type');
    expect($types)->not->toContain(CatalogResourceType::Audiobook)
        ->and($types)->not->toContain(CatalogResourceType::Video);
});

it('expands a parent category id to also match dissertations/avtoreferats tagged only with its child', function () {
    $parent = Category::factory()->create();
    $child = Category::factory()->create(['parent_id' => $parent->id]);
    Dissertation::factory()->create(['category_id' => $child->id]);

    $res = $this->get(route('catalog', ['categories' => [$parent->id]]));

    expect($res->viewData('total'))->toBe(1);
});

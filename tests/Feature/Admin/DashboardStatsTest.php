<?php

use App\Models\Audiobook;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Category;
use App\Models\Language;
use App\Models\Video;

beforeEach(fn () => actingAsAdmin());

it('no longer shows the loan/overdue KPI cards — those live on the Berilgan kitoblar page', function () {
    // "Muddati o'tgan" itself still legitimately appears sitewide via the
    // always-present header bell badge/tooltip — only the dashboard's own
    // dedicated KPI cards (uniquely identified by "Hozir berilgan" and the
    // "ko'rish uchun bosing" card caption) are what's being removed here.
    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertDontSee('Hozir berilgan')
        ->assertDontSee('ko‘rish uchun bosing');
});

it('groups charts under "Foydalanuvchi statistikasi" and "Fond statistikasi" sections', function () {
    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Foydalanuvchi statistikasi')
        ->assertSee('Fond statistikasi')
        ->assertDontSee('Kitob nomi');
});

it('shows a nomda/nusxada toggle on the "Shakli bo‘yicha" chart, with audio/video counts included', function () {
    $books = Book::factory()->count(2)->create();
    BookCopy::factory()->count(3)->create(['book_id' => $books->first()->id, 'format' => 'print']);
    Audiobook::factory()->count(2)->create();
    Video::factory()->create();

    $res = $this->get(route('admin.dashboard'));

    $res->assertOk()
        ->assertSee('Shakli bo‘yicha')
        ->assertSee('Bosma')
        ->assertSee('Elektron')
        ->assertSee('Brayl')
        ->assertSee('Audio')
        ->assertSee('Video');

    expect($res->viewData('titlesByFormat')['print'])->toBe(1)
        ->and($res->viewData('copiesByFormat')['print'])->toBe(3)
        ->and($res->viewData('audiobooksTotal'))->toBe(2)
        ->and($res->viewData('videosTotal'))->toBe(1);
});

it('replaces the computer-status donut with a language-by-book donut', function () {
    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertDontSee('Kompyuterlar holati')
        ->assertDontSee('Elektron o‘qish zali')
        ->assertSee('Tillar bo‘yicha');
});

it('shows a "asosiy toifalar" chart with a nomda/nusxada toggle, one bar per top-level category', function () {
    $parent = Category::factory()->create(['name' => ['uz' => 'Sinov toifasi'], 'parent_id' => null]);
    $child = Category::factory()->create(['name' => ['uz' => 'Sinov bola toifasi'], 'parent_id' => $parent->id]);
    $book = Book::factory()->create();
    $book->categories()->attach($child->id);
    BookCopy::factory()->count(2)->create(['book_id' => $book->id]);

    $res = $this->get(route('admin.dashboard'));

    $res->assertOk()->assertSee('Asosiy toifalar bo‘yicha')->assertSee('Sinov toifasi');

    $stats = $res->viewData('categoryStats');
    $index = array_search('Sinov toifasi', $stats['labels']);

    expect($index)->not->toBeFalse()
        ->and($stats['titles'][$index])->toBe(1) // rolled up from the child category
        ->and($stats['copies'][$index])->toBe(2);
});

it('counts a book with an online-readable PDF as "elektron", even with no electronic BookCopy row', function () {
    // Regression: the format donut used to count only BookCopy rows, so a
    // real digitized book (electronic_file set, actually read online) with
    // no separate "electronic copy" catalog record showed up as 0.
    Book::factory()->create(['electronic_file' => 'books/electronic/real.pdf']);

    $res = $this->get(route('admin.dashboard'));

    expect($res->viewData('copiesByFormat')['electronic'])->toBe(1);
});

it('does not double-count a book that has both an electronic_file and an electronic BookCopy row', function () {
    $book = Book::factory()->create(['electronic_file' => 'books/electronic/real.pdf']);
    BookCopy::factory()->create(['book_id' => $book->id, 'format' => 'electronic']);

    $res = $this->get(route('admin.dashboard'));

    expect($res->viewData('copiesByFormat')['electronic'])->toBe(1);
});

it('groups the language donut by each book’s primary language', function () {
    $uz = Language::factory()->create(['name' => 'Ochiq til nomi']);
    Book::factory()->create(['language_id' => $uz->id]);

    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Ochiq til nomi');
});

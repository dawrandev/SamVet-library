<?php

use App\Models\Audiobook;
use App\Models\Book;
use App\Models\BookCopy;
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

it('replaces the "Kitob nomi"/"Foydalanuvchi" KPI cards with a single nomda/nusxada donut', function () {
    $books = Book::factory()->count(2)->create();
    BookCopy::factory()->count(3)->create(['book_id' => $books->first()->id]);

    $res = $this->get(route('admin.dashboard'));

    $res->assertOk()
        ->assertSee('Kitob nomi')
        ->assertSee('Nomda')
        ->assertSee('Nusxada');

    expect($res->viewData('booksTotal'))->toBe(2)
        ->and($res->viewData('copiesTotal'))->toBe(3);
});

it('adds audio and video counts to the "Nusxalar shakli" donut', function () {
    Audiobook::factory()->count(2)->create();
    Video::factory()->create();

    $res = $this->get(route('admin.dashboard'));

    $res->assertOk()->assertSee('Audio')->assertSee('Video');

    expect($res->viewData('audiobooksTotal'))->toBe(2)
        ->and($res->viewData('videosTotal'))->toBe(1);
});

it('replaces the computer-status donut with a language-by-book donut', function () {
    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertDontSee('Kompyuterlar holati')
        ->assertDontSee('Elektron o‘qish zali')
        ->assertSee('Tillar bo‘yicha');
});

it('shows a copies-by-format donut (bosma/elektron/brayl)', function () {
    $book = Book::factory()->create();
    BookCopy::factory()->create(['book_id' => $book->id, 'format' => 'print']);
    BookCopy::factory()->create(['book_id' => $book->id, 'format' => 'electronic']);

    $res = $this->get(route('admin.dashboard'));

    $res->assertOk()
        ->assertSee('Nusxalar shakli')
        ->assertSee('Bosma')
        ->assertSee('Elektron')
        ->assertSee('Brayl');
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

<?php

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Language;

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

it('groups the language donut by each book’s primary language', function () {
    $uz = Language::factory()->create(['name' => 'Ochiq til nomi']);
    Book::factory()->create(['language_id' => $uz->id]);

    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Ochiq til nomi');
});

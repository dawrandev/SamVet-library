<?php

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Journal;
use App\Models\News;
use App\Models\PublicationPlace;

it('renders the core public pages', function (string $path) {
    $this->get($path)->assertOk();
})->with([
    'home' => '/',
    'catalog' => '/katalog',
    'sections' => '/bolimlar',
    'periodicals' => '/jurnallar',
    'statistics' => '/statistika',
    'news' => '/yangiliklar',
    'login' => '/kirish',
]);

it('shows a book detail page but hides admin-only data', function () {
    $book = Book::factory()->create(['title' => 'Ochiq kitob', 'print_run' => 7777]);
    BookCopy::factory()->create([
        'book_id' => $book->id,
        'inventory_number' => 'INV-SECRET',
        'price' => 88888,
    ]);

    $res = $this->get(route('book.show', $book->slug));

    $res->assertOk()->assertSee('Ochiq kitob');
    // Inventory number, price and print run are librarian-only.
    $res->assertDontSee('INV-SECRET')
        ->assertDontSee('88888')
        ->assertDontSee('7777');
    // The raw protected file path must never leak into the public HTML.
    $res->assertDontSee('books/electronic');
});

it('shows a journal detail page but hides library-internal fields', function () {
    $place = PublicationPlace::factory()->create(['name' => ['uz' => 'Maxfiy shahar']]);
    $journal = Journal::factory()->create([
        'name' => 'Ochiq jurnal',
        'index' => 'IDX-SECRET',
        'founder' => 'Maxfiy muassis',
        'publisher' => ['uz' => 'Maxfiy nashriyot'],
        'publication_place_id' => $place->id,
        'periodicity' => 'weekly',
        'periodicity_count' => 3,
    ]);

    $res = $this->get(route('journal.show', $journal->slug));

    $res->assertOk()->assertSee('Ochiq jurnal')
        ->assertSee('3 marta / Haftalik');
    // Indeks, muassis, nashr joyi and nashriyoti are library-internal —
    // admin-only, never shown on the public site.
    $res->assertDontSee('IDX-SECRET')
        ->assertDontSee('Maxfiy muassis')
        ->assertDontSee('Maxfiy nashriyot')
        ->assertDontSee('Maxfiy shahar');
});

it('shows a published news item and hides drafts', function () {
    $published = News::factory()->create();
    $draft = News::factory()->draft()->create();

    $this->get(route('news.index'))->assertOk();
    $this->get(route('news.show', $published->slug))->assertOk();
    // A draft (no published_at) is not publicly readable.
    $this->get(route('news.show', $draft->slug))->assertNotFound();
});

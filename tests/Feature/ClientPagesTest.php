<?php

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\News;

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

it('shows a published news item and hides drafts', function () {
    $published = News::factory()->create();
    $draft = News::factory()->draft()->create();

    $this->get(route('news.index'))->assertOk();
    $this->get(route('news.show', $published->slug))->assertOk();
    // A draft (no published_at) is not publicly readable.
    $this->get(route('news.show', $draft->slug))->assertNotFound();
});

<?php

use App\Models\Book;
use App\Models\BookReading;
use App\Models\Reader;

beforeEach(fn () => actingAsAdmin());

it('shows a reader’s online reading history with the book title and exact date/time', function () {
    $reader = Reader::factory()->create();
    $book = Book::factory()->create(['title' => 'Onlayn o‘qilgan kitob']);
    BookReading::factory()->create([
        'reader_id' => $reader->id,
        'book_id' => $book->id,
        'read_at' => '2026-07-15 14:30:00',
    ]);

    $this->get(route('admin.readers.show', $reader))
        ->assertSee('Onlayn o‘qishlar')
        ->assertSee('Onlayn o‘qilgan kitob')
        ->assertSee('15.07.2026 14:30');
});

it('shows an empty state when a reader has no online reads', function () {
    $reader = Reader::factory()->create();

    $this->get(route('admin.readers.show', $reader))
        ->assertSee('Hozircha onlayn o‘qish yo‘q');
});

it('only shows one reader’s own online reads, not another reader’s', function () {
    $readerA = Reader::factory()->create();
    $readerB = Reader::factory()->create();
    BookReading::factory()->create([
        'reader_id' => $readerB->id,
        'book_id' => Book::factory()->create(['title' => 'Boshqa foydalanuvchi kitobi'])->id,
    ]);

    $this->get(route('admin.readers.show', $readerA))
        ->assertDontSee('Boshqa foydalanuvchi kitobi');
});

<?php

use App\Models\Book;
use App\Models\BookReading;
use App\Models\Reader;

beforeEach(fn () => actingAsAdmin());

it('no longer shows the removed recently-lent-books widget', function () {
    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertDontSee('So‘nggi berilgan kitoblar');
});

it('shows online readings within the default (today) range, with a total', function () {
    $reader = Reader::factory()->create(['full_name' => 'Aliyev Ali']);
    $book = Book::factory()->create(['title' => 'Bugungi kitob']);
    BookReading::factory()->create(['reader_id' => $reader->id, 'book_id' => $book->id, 'read_at' => now()]);

    $oldReader = Reader::factory()->create();
    $oldBook = Book::factory()->create(['title' => 'Eski kitob']);
    BookReading::factory()->create(['reader_id' => $oldReader->id, 'book_id' => $oldBook->id, 'read_at' => now()->subDays(5)]);

    $res = $this->get(route('admin.dashboard'));

    $res->assertOk()
        ->assertSee('Aliyev Ali')
        ->assertSee('Bugungi kitob')
        ->assertDontSee('Eski kitob');
});

it('filters online readings by an explicit from/to range', function () {
    $reader = Reader::factory()->create(['full_name' => 'Vositov Vosit']);
    $book = Book::factory()->create(['title' => 'Oraliqdagi kitob']);
    BookReading::factory()->create([
        'reader_id' => $reader->id,
        'book_id' => $book->id,
        'read_at' => '2026-06-15 10:00:00',
    ]);

    $outsideReader = Reader::factory()->create();
    $outsideBook = Book::factory()->create(['title' => 'Chetdagi kitob']);
    BookReading::factory()->create([
        'reader_id' => $outsideReader->id,
        'book_id' => $outsideBook->id,
        'read_at' => '2026-01-01 10:00:00',
    ]);

    $res = $this->get(route('admin.dashboard', [
        'from' => '2026-06-01T00:00',
        'to' => '2026-06-30T23:59',
    ]));

    $res->assertOk()
        ->assertSee('Vositov Vosit')
        ->assertSee('Oraliqdagi kitob')
        ->assertDontSee('Chetdagi kitob');
});

it('shows the total reading count for the filtered range', function () {
    $book = Book::factory()->create();
    BookReading::factory()->count(3)->create(['book_id' => $book->id, 'read_at' => now()]);

    $this->get(route('admin.dashboard'))
        ->assertSee('Jami: 3');
});
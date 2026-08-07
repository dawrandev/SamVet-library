<?php

use App\Models\Avtoreferat;
use App\Models\Book;
use App\Models\OnlineRead;
use App\Models\Reader;
use App\Models\Video;

beforeEach(fn () => actingAsAdmin());

it('no longer shows the removed recently-lent-books widget', function () {
    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertDontSee('So‘nggi berilgan kitoblar');
});

it('shows online readings within the default (today) range, with a total', function () {
    $reader = Reader::factory()->create(['full_name' => 'Aliyev Ali']);
    $book = Book::factory()->create(['title' => 'Bugungi kitob']);
    OnlineRead::factory()->create(['reader_id' => $reader->id, 'readable_type' => 'book', 'readable_id' => $book->id, 'read_at' => now()]);

    $oldReader = Reader::factory()->create();
    $oldBook = Book::factory()->create(['title' => 'Eski kitob']);
    OnlineRead::factory()->create(['reader_id' => $oldReader->id, 'readable_type' => 'book', 'readable_id' => $oldBook->id, 'read_at' => now()->subDays(5)]);

    $res = $this->get(route('admin.dashboard'));

    $res->assertOk()
        ->assertSee('Aliyev Ali')
        ->assertSee('Bugungi kitob')
        ->assertDontSee('Eski kitob');
});

it('filters online readings by an explicit from/to range', function () {
    $reader = Reader::factory()->create(['full_name' => 'Vositov Vosit']);
    $book = Book::factory()->create(['title' => 'Oraliqdagi kitob']);
    OnlineRead::factory()->create([
        'reader_id' => $reader->id,
        'readable_type' => 'book',
        'readable_id' => $book->id,
        'read_at' => '2026-06-15 10:00:00',
    ]);

    $outsideReader = Reader::factory()->create();
    $outsideBook = Book::factory()->create(['title' => 'Chetdagi kitob']);
    OnlineRead::factory()->create([
        'reader_id' => $outsideReader->id,
        'readable_type' => 'book',
        'readable_id' => $outsideBook->id,
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
    OnlineRead::factory()->count(3)->create(['readable_type' => 'book', 'readable_id' => $book->id, 'read_at' => now()]);

    $this->get(route('admin.dashboard'))
        ->assertSee('Jami: 3');
});

it('includes video and avtoreferat reads alongside books in the same widget, with their own type label', function () {
    $video = Video::factory()->create(['name' => 'Bugungi video']);
    OnlineRead::factory()->create(['readable_type' => 'video', 'readable_id' => $video->id, 'read_at' => now()]);

    $avtoreferat = Avtoreferat::factory()->create(['title' => 'Bugungi avtoreferat']);
    OnlineRead::factory()->create(['readable_type' => 'avtoreferat', 'readable_id' => $avtoreferat->id, 'read_at' => now()]);

    $this->get(route('admin.dashboard'))
        ->assertSee('Bugungi video')
        ->assertSee('Video')
        ->assertSee('Bugungi avtoreferat')
        ->assertSee('Avtoreferat')
        ->assertSee('Jami: 2');
});

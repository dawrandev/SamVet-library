<?php

use App\Models\Book;
use App\Models\Reader;

it('renders the public home page', function () {
    $this->get('/')->assertOk();
});

it('creates a book through the factory with an auto-generated slug', function () {
    $book = Book::factory()->create(['title' => 'Sinov kitobi']);

    expect($book->slug)->not->toBeEmpty();
    $this->assertDatabaseHas('books', ['title' => 'Sinov kitobi']);
});

it('gives a new reader the shared password (hashed) via the observer', function () {
    $reader = Reader::factory()->create();

    expect($reader->password)->not->toBeNull()
        ->and(Hash::check(config('arm.reader_default_password'), $reader->password))->toBeTrue();
});

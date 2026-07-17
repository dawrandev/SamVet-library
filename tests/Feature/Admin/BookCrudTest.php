<?php

use App\Models\Book;
use App\Models\BookType;
use App\Models\Language;
use App\Models\PublicationPlace;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => actingAsAdmin());

it('creates a book with a plain-text publisher and a place lookup', function () {
    $type = BookType::factory()->create();
    $lang = Language::factory()->create();
    $place = PublicationPlace::factory()->create();

    $this->post(route('admin.books.store'), [
        'title' => 'Yangi kitob',
        'book_type_id' => $type->id,
        'language_id' => $lang->id,
        'publisher' => 'Fan',
        'publication_place_id' => $place->id,
        'publication_year' => 2024,
    ])->assertRedirect();

    $book = Book::firstWhere('title', 'Yangi kitob');
    expect($book)->not->toBeNull()
        ->and($book->publisher)->toBe('Fan')
        ->and($book->publication_place_id)->toBe($place->id)
        ->and($book->slug)->not->toBeEmpty();
});

it('requires a title', function () {
    $this->from(route('admin.books.create'))
        ->post(route('admin.books.store'), [])
        ->assertSessionHasErrors('title');
});

it('rejects a non-pdf electronic file', function () {
    $type = BookType::factory()->create();

    $this->from(route('admin.books.create'))
        ->post(route('admin.books.store'), [
            'title' => 'X',
            'book_type_id' => $type->id,
            'electronic_file' => UploadedFile::fake()->create('note.txt', 10, 'text/plain'),
        ])
        ->assertSessionHasErrors('electronic_file');
});

it('stores the uploaded pdf on the protected (local) disk', function () {
    Storage::fake('local');
    $type = BookType::factory()->create();

    $this->post(route('admin.books.store'), [
        'title' => 'PDF kitob',
        'book_type_id' => $type->id,
        'electronic_file' => UploadedFile::fake()->create('book.pdf', 300, 'application/pdf'),
    ])->assertRedirect();

    $book = Book::firstWhere('title', 'PDF kitob');
    expect($book->electronic_file)->not->toBeNull();
    Storage::disk('local')->assertExists($book->electronic_file);
});

it('updates a book', function () {
    $book = Book::factory()->create(['title' => 'Eski nom']);

    $this->put(route('admin.books.update', $book), [
        'title' => 'Yangi nom',
        'book_type_id' => $book->book_type_id,
        'language_id' => $book->language_id,
    ])->assertRedirect();

    expect($book->fresh()->title)->toBe('Yangi nom');
});

it('deletes a book', function () {
    $book = Book::factory()->create();

    $this->delete(route('admin.books.destroy', $book))->assertRedirect();

    $this->assertDatabaseMissing('books', ['id' => $book->id]);
});

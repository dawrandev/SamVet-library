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

it('saves target audience, size and print sheets, all nullable', function () {
    $this->post(route('admin.books.store'), [
        'title' => 'To‘liq maydonli kitob',
        'target_audience' => 'Talabalar uchun',
        'size_cm' => 21,
        'print_sheets' => '20,5',
    ])->assertRedirect();

    $book = Book::firstWhere('title', 'To‘liq maydonli kitob');
    expect($book->target_audience)->toBe('Talabalar uchun')
        ->and($book->size_cm)->toBe(21)
        ->and($book->print_sheets)->toBe('20,5');
});

it('creates a book with target_audience, size_cm and print_sheets left blank', function () {
    $this->post(route('admin.books.store'), [
        'title' => 'Maydonlarsiz kitob',
    ])->assertSessionDoesntHaveErrors(['target_audience', 'size_cm', 'print_sheets']);

    $book = Book::firstWhere('title', 'Maydonlarsiz kitob');
    expect($book)->not->toBeNull()
        ->and($book->target_audience)->toBeNull()
        ->and($book->size_cm)->toBeNull()
        ->and($book->print_sheets)->toBeNull();
});

it('shows the new fields on the book show page', function () {
    $book = Book::factory()->create([
        'target_audience' => 'Kattalar uchun',
        'size_cm' => 22,
        'print_sheets' => '18,0',
    ]);

    $this->get(route('admin.books.show', $book))
        ->assertSee('Kimlar uchun')
        ->assertSee('Kattalar uchun')
        ->assertSee('O‘lchami')
        ->assertSee('22 sm')
        ->assertSee('Bosma taboq')
        ->assertSee('18,0');
});

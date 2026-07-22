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

it('syncs the single chosen language into the languages pivot too', function () {
    $type = BookType::factory()->create();
    $lang = Language::factory()->create();

    $this->post(route('admin.books.store'), [
        'title' => 'Bir tilli kitob',
        'book_type_id' => $type->id,
        'language_id' => $lang->id,
    ])->assertRedirect();

    $book = Book::firstWhere('title', 'Bir tilli kitob');
    expect($book->language_id)->toBe($lang->id)
        ->and($book->languages->pluck('id')->all())->toBe([$lang->id])
        ->and($book->parallel_titles)->toBeNull();
});

it('creates a book with parallel titles and several languages, using the first as the primary language', function () {
    $type = BookType::factory()->create();
    $uz = Language::factory()->create(['name' => 'Oʻzbek']);
    $ru = Language::factory()->create(['name' => 'Rus']);
    $en = Language::factory()->create(['name' => 'Ingliz']);

    $this->post(route('admin.books.store'), [
        'title' => 'Veterinariya asoslari',
        'book_type_id' => $type->id,
        'parallel_titles' => ['Основы ветеринарии', 'Fundamentals of Veterinary Medicine'],
        // Deliberately not first alphabetically/numerically — order decides the primary.
        'language_ids' => [$ru->id, $uz->id, $en->id],
    ])->assertRedirect();

    $book = Book::firstWhere('title', 'Veterinariya asoslari');
    expect($book->parallel_titles)->toBe(['Основы ветеринарии', 'Fundamentals of Veterinary Medicine'])
        // The 1st selected language ($ru) becomes the primary — what stats/filters read.
        ->and($book->language_id)->toBe($ru->id)
        ->and($book->languages->pluck('id')->sort()->values()->all())->toBe(collect([$ru->id, $uz->id, $en->id])->sort()->values()->all());
});

it('drops blank parallel title rows', function () {
    $type = BookType::factory()->create();

    $this->post(route('admin.books.store'), [
        'title' => 'Bo‘sh qatorli kitob',
        'book_type_id' => $type->id,
        'parallel_titles' => ['Haqiqiy sarlavha', '', '   '],
    ])->assertRedirect();

    $book = Book::firstWhere('title', 'Bo‘sh qatorli kitob');
    expect($book->parallel_titles)->toBe(['Haqiqiy sarlavha']);
});

it('updates a book’s parallel titles and languages, replacing the previous set', function () {
    $uz = Language::factory()->create();
    $ru = Language::factory()->create();
    $book = Book::factory()->create(['language_id' => $uz->id, 'parallel_titles' => null]);
    $book->languages()->sync([$uz->id]);

    $this->put(route('admin.books.update', $book), [
        'title' => $book->title,
        'parallel_titles' => ['Параллель сарлавҳа'],
        'language_ids' => [$ru->id, $uz->id],
    ])->assertRedirect();

    $book->refresh();
    expect($book->parallel_titles)->toBe(['Параллель сарлавҳа'])
        ->and($book->language_id)->toBe($ru->id)
        ->and($book->languages->pluck('id')->sort()->values()->all())->toBe(collect([$ru->id, $uz->id])->sort()->values()->all());
});

it('shows the parallel title and all languages on the book show page', function () {
    $uz = Language::factory()->create(['name' => 'Oʻzbek']);
    $ru = Language::factory()->create(['name' => 'Rus']);
    $book = Book::factory()->create(['language_id' => $uz->id, 'parallel_titles' => ['Параллельный заголовок']]);
    $book->languages()->sync([$uz->id, $ru->id]);

    $this->get(route('admin.books.show', $book))
        ->assertSee('Параллельный заголовок')
        ->assertSee($uz->name)
        ->assertSee($ru->name);
});

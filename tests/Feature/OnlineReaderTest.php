<?php

use App\Models\Book;
use Illuminate\Support\Facades\Storage;

it('redirects a guest from the reader page to the reader login', function () {
    $book = Book::factory()->withPdf()->create();

    $this->get(route('read.book', $book->slug))->assertRedirect(route('reader.login'));
});

it('redirects a guest from the raw file stream to login (no unauthenticated access)', function () {
    $book = Book::factory()->withPdf()->create();

    $this->get(route('read.book.file', $book->slug))->assertRedirect(route('reader.login'));
});

it('lets a signed-in reader open the reader page', function () {
    actingAsReader();
    $book = Book::factory()->withPdf()->create();

    $this->get(route('read.book', $book->slug))->assertOk();
});

it('streams the pdf inline, no-store, and never as a download', function () {
    Storage::fake('local');
    $path = 'books/electronic/x.pdf';
    Storage::disk('local')->put($path, '%PDF-1.7 fake');

    actingAsReader();
    $book = Book::factory()->withPdf($path)->create();

    $res = $this->get(route('read.book.file', $book->slug));

    $res->assertOk();
    expect($res->headers->get('content-type'))->toContain('application/pdf')
        ->and($res->headers->get('content-disposition'))->toContain('inline')
        ->and($res->headers->get('content-disposition'))->not->toContain('attachment')
        ->and($res->headers->get('cache-control'))->toContain('no-store');
});

it('404s when a reader opens a book that has no stored pdf', function () {
    actingAsReader();
    $book = Book::factory()->create(); // no electronic_file

    $this->get(route('read.book', $book->slug))->assertNotFound();
    $this->get(route('read.book.file', $book->slug))->assertNotFound();
});

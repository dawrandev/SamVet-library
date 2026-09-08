<?php

use App\Models\Book;
use Illuminate\Support\Facades\Storage;

/**
 * The PDF stream used to answer every request with the entire file, however
 * little of it the reader was looking at. On the production host — a cPanel
 * account capped at 1 MB/s of disk I/O — opening page one of a 100 MB book
 * meant reading 100 MB and holding a PHP worker for a hundred seconds, every
 * time, because `no-store` also rules out the browser reusing it.
 *
 * With Range support advertised, PDF.js fetches the file's structure and then
 * only the pages actually on screen.
 */
const PDF_BODY = '%PDF-1.7 0123456789abcdefghijklmnopqrstuvwxyz';

function bookWithPdf(): Book
{
    Storage::fake('local');
    $path = 'books/electronic/range.pdf';
    Storage::disk('local')->put($path, PDF_BODY);

    actingAsReader();

    return Book::factory()->withPdf($path)->create();
}

it('tells the client that ranges may be requested', function () {
    $book = bookWithPdf();

    $response = $this->get(route('read.book.file', $book->slug));

    // Advertised on the full response too — this header is what makes PDF.js
    // switch to range fetching in the first place.
    $response->assertOk();
    expect($response->headers->get('accept-ranges'))->toBe('bytes')
        ->and($response->streamedContent())->toBe(PDF_BODY);
});

it('serves only the requested bytes of a pdf', function () {
    $book = bookWithPdf();

    $response = $this->get(route('read.book.file', $book->slug), ['Range' => 'bytes=0-8']);

    $size = strlen(PDF_BODY);

    $response->assertStatus(206);
    expect($response->headers->get('content-range'))->toBe("bytes 0-8/{$size}")
        ->and($response->headers->get('content-length'))->toBe('9')
        ->and($response->streamedContent())->toBe(substr(PDF_BODY, 0, 9));
});

it('serves a range from the middle of the file', function () {
    $book = bookWithPdf();

    $response = $this->get(route('read.book.file', $book->slug), ['Range' => 'bytes=10-19']);

    $response->assertStatus(206);
    expect($response->streamedContent())->toBe(substr(PDF_BODY, 10, 10));
});

it('reads to the end of the file for an open-ended range', function () {
    $book = bookWithPdf();

    $response = $this->get(route('read.book.file', $book->slug), ['Range' => 'bytes=30-']);

    $response->assertStatus(206);
    expect($response->streamedContent())->toBe(substr(PDF_BODY, 30));
});

it('answers 416 for a range past the end of the file', function () {
    $book = bookWithPdf();

    // The old media implementation clamped only the upper bound, so this
    // produced a negative Content-Length and a malformed body instead.
    $this->get(route('read.book.file', $book->slug), ['Range' => 'bytes=99999-'])
        ->assertStatus(416);
});

it('keeps the protections in place on a partial response', function () {
    $book = bookWithPdf();

    $response = $this->get(route('read.book.file', $book->slug), ['Range' => 'bytes=0-4']);

    // Range support must not become a way around the reading protections:
    // still inline, still uncached, still typed as PDF.
    expect($response->headers->get('content-type'))->toContain('application/pdf')
        ->and($response->headers->get('content-disposition'))->toContain('inline')
        ->and($response->headers->get('content-disposition'))->not->toContain('attachment')
        ->and($response->headers->get('cache-control'))->toContain('no-store');
});

it('still requires a signed-in reader for a ranged request', function () {
    Storage::fake('local');
    $path = 'books/electronic/range.pdf';
    Storage::disk('local')->put($path, PDF_BODY);

    $book = Book::factory()->withPdf($path)->create();

    $this->get(route('read.book.file', $book->slug), ['Range' => 'bytes=0-8'])
        ->assertRedirect(route('reader.login'));
});

<?php

use App\Models\Book;
use App\Models\Reader;
use Illuminate\Support\Facades\Storage;
use Laravel\Dusk\Browser;

it('lets a reader sign in and read a book online (PDF renders, no download)', function () {
    Reader::factory()->create(['id_number' => 'BTDUSK1']);

    $path = 'books/electronic/dusk-'.uniqid().'.pdf';
    Storage::disk('local')->put($path, file_get_contents(base_path('tests/fixtures/sample.pdf')));
    $book = Book::factory()->withPdf($path)->create(['title' => 'Dusk kitobi']);

    $this->browse(function (Browser $browser) use ($book) {
        $browser->visit('/kirish')
            ->type('id_number', 'BTDUSK1')
            ->type('password', 'arm777')
            ->press('Kirish')
            ->waitForLocation('/', 15)
            ->visit('/oqish/kitob/'.$book->slug)
            // PDF.js drops the `invisible` class once the first page is painted.
            ->waitFor('[data-reader-canvas]:not(.invisible)', 25)
            ->assertSee('Yuklab olish mavjud emas');

        expect($browser->text('[data-reader-total]'))->not->toBe('—');
    });

    Storage::disk('local')->delete($book->electronic_file);
});

it('blocks a guest from the reader and sends them to sign in', function () {
    $path = 'books/electronic/dusk-'.uniqid().'.pdf';
    Storage::disk('local')->put($path, file_get_contents(base_path('tests/fixtures/sample.pdf')));
    $book = Book::factory()->withPdf($path)->create();

    $this->browse(function (Browser $browser) use ($book) {
        $browser->visit('/oqish/kitob/'.$book->slug)
            ->assertPathIs('/kirish')
            ->assertSee('Kirish');
    });

    Storage::disk('local')->delete($book->electronic_file);
});

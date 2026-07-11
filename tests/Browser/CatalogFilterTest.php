<?php

use App\Models\Book;
use App\Models\BookType;
use Laravel\Dusk\Browser;

it('filters the catalog when a sidebar facet is toggled (Alpine auto-submit)', function () {
    $type = BookType::factory()->create();
    Book::factory()->count(2)->create(['book_type_id' => $type->id]);
    Book::factory()->count(3)->create();

    $this->browse(function (Browser $browser) use ($type) {
        $browser->visit('/katalog')
            ->assertSee('5 ta natija')
            ->check("input[name=\"types[]\"][value=\"{$type->id}\"]")
            ->waitForText('2 ta natija', 10)
            // The active-filter chip row appears with a clear-all link.
            ->assertSee('Barchasini tozalash');
    });
});

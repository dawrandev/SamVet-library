<?php

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Category;
use Laravel\Dusk\Browser;

/**
 * Regression guard for a real production bug: without an explicit, shared
 * y-axis max, ApexCharts auto-scales the axis to whichever series (nusxa or
 * nomi) is currently shown. Since title counts are always much smaller than
 * copy counts, toggling to "Nomi" rescaled the axis to fit the smaller data —
 * a bar sitting near its own (now much smaller) max rendered almost as tall
 * as before, looking like it grew instead of shrunk, even though the
 * tooltip/value was correct. Fixed via a fixed yaxis.max spanning both
 * series (resources/js/admin/charts.js, bar()) — this test pins the visible
 * (pixel) effect, not just the underlying data, since the bug was purely a
 * rendering issue the data-level dashboard tests could never have caught.
 */
it('shrinks the category bar chart proportionally, not visually inflated, when toggling nusxa -> nomi', function () {
    $admin = actingAsAdmin();

    $top = Category::factory()->create(['name' => ['uz' => 'Sinov toifasi'], 'parent_id' => null]);

    // A large copy count relative to a small title count — the exact shape
    // that exposed the bug (one title, several copies of it).
    $book = Book::factory()->create();
    $book->categories()->attach($top->id);
    BookCopy::factory()->count(10)->create(['book_id' => $book->id]);

    $this->browse(function (Browser $browser) use ($admin) {
        $browser->loginAs($admin)
            ->visit('/admin')
            ->waitFor('#chart-category-bar', 10)
            ->scrollIntoView('#chart-category-bar')
            ->pause(1000);

        $tallestBarHeight = "Math.max(...Array.from(document.querySelectorAll('#chart-category-bar .apexcharts-bar-area')).map(p => p.getBoundingClientRect().height));";
        $nusxaHeight = $browser->script("return {$tallestBarHeight}")[0];

        $browser->script("document.getElementById('chart-category-bar').closest('.rounded-2xl').querySelector('[data-bar-mode=\"titles\"]').click();");
        $browser->pause(1000);

        $nomiHeight = $browser->script("return {$tallestBarHeight}")[0];

        // 1 title vs 10 copies — nomi's bar must render clearly SHORTER than
        // nusxa's, not equal to or taller than it.
        expect($nomiHeight)->toBeLessThan($nusxaHeight * 0.5);
    });
});

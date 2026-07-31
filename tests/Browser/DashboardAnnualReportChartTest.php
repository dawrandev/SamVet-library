<?php

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Category;
use App\Models\Language;
use Laravel\Dusk\Browser;

/**
 * Regression guard for the same axis-rescaling bug class fixed in
 * DashboardChartTest.php, but for the annual report's stacked bar, which has
 * 3 dimensions (shakli/toifasi/tili) x 2 modes (nusxa/nomi) = 6 renderable
 * states instead of 2. Every state must share the exact same fixed y-axis
 * max (resources/js/admin/charts.js, stackedBar()) — this test reads the
 * rendered y-axis tick labels themselves and asserts they are byte-identical
 * across all 6 states, which is the only check that actually catches "the
 * axis silently rescaled" (a pixel-ratio check alone can't distinguish that
 * from "the data legitimately changed").
 */
it('never changes the annual report y-axis scale across any dimension/mode combination', function () {
    $admin = actingAsAdmin();

    $category = Category::factory()->create(['name' => ['uz' => 'Sinov toifasi'], 'parent_id' => null]);
    $language = Language::factory()->create(['name' => ['uz' => 'Sinov tili', 'ru' => 'Sinov tili', 'kk' => 'Sinov tili']]);
    $book = Book::factory()->create(['language_id' => $language->id]);
    $book->categories()->attach($category->id);
    BookCopy::factory()->count(10)->create(['book_id' => $book->id, 'acquisition_act_at' => '2024-05-01']);

    $this->browse(function (Browser $browser) use ($admin) {
        $browser->loginAs($admin)
            ->visit('/admin')
            ->waitFor('#chart-annual-report', 10)
            ->scrollIntoView('#chart-annual-report')
            ->pause(1000);

        $readTicks = "Array.from(document.querySelectorAll('#chart-annual-report .apexcharts-yaxis-texts-g text')).map(t => t.textContent).join('|')";
        $baseline = $browser->script("return {$readTicks}")[0];
        expect($baseline)->not->toBe('');

        $clickDimension = fn (string $dim) => $browser->script(
            "document.getElementById('chart-annual-report').closest('.rounded-2xl').querySelector('[data-stacked-bar-dimension=\"{$dim}\"]').click();"
        );
        $clickMode = fn (string $mode) => $browser->script(
            "document.getElementById('chart-annual-report').closest('.rounded-2xl').querySelector('[data-bar-mode=\"{$mode}\"]').click();"
        );

        // Full cycle, not just adjacent pairs, so drift anywhere is caught:
        // format (default) -> category -> language -> format, in nusxa mode,
        // then the same cycle again in nomi mode.
        foreach (['category', 'language', 'format'] as $dim) {
            $clickDimension($dim);
            $browser->pause(600);
            expect($browser->script("return {$readTicks}")[0])->toBe($baseline);
        }

        $clickMode('titles');
        $browser->pause(600);
        expect($browser->script("return {$readTicks}")[0])->toBe($baseline);

        foreach (['category', 'language', 'format'] as $dim) {
            $clickDimension($dim);
            $browser->pause(600);
            expect($browser->script("return {$readTicks}")[0])->toBe($baseline);
        }

        $clickMode('copies');
        $browser->pause(600);
        expect($browser->script("return {$readTicks}")[0])->toBe($baseline);
    });
});

/**
 * Supplementary sanity check (not the authoritative guard — see above):
 * with 10 copies vs 1 title for the same book, the nusxa stacked bar must
 * still render clearly taller than the nomi one, confirming the fixed axis
 * didn't accidentally flatten real differences into invisibility either.
 */
it('still visibly shrinks the annual report bar when toggling nusxa -> nomi', function () {
    $admin = actingAsAdmin();

    $book = Book::factory()->create();
    BookCopy::factory()->count(10)->create(['book_id' => $book->id, 'acquisition_act_at' => '2024-05-01']);

    $this->browse(function (Browser $browser) use ($admin) {
        $browser->loginAs($admin)
            ->visit('/admin')
            ->waitFor('#chart-annual-report', 10)
            ->scrollIntoView('#chart-annual-report')
            ->pause(1000);

        $tallestBarHeight = "Math.max(...Array.from(document.querySelectorAll('#chart-annual-report .apexcharts-bar-area')).map(p => p.getBoundingClientRect().height));";
        $nusxaHeight = $browser->script("return {$tallestBarHeight}")[0];

        $browser->script("document.getElementById('chart-annual-report').closest('.rounded-2xl').querySelector('[data-bar-mode=\"titles\"]').click();");
        $browser->pause(800);

        $nomiHeight = $browser->script("return {$tallestBarHeight}")[0];

        expect($nomiHeight)->toBeLessThan($nusxaHeight * 0.5);
    });
});

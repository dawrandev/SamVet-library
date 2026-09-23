<?php

use App\Support\ReaderCard\CardPalette;
use App\Support\ReaderCard\Layout;
use App\Support\ReaderCard\TextFitter;

/*
 * Layout pins what dompdf was measured to do (see the class docblock). These
 * numbers are observations, not preferences: if a dompdf upgrade changes its
 * line-box maths, the card will drift and these are where to look first.
 */
it('converts a wanted line spacing into the CSS line-height dompdf needs', function () {
    // Inter spaces lines 1.331x the CSS value apart (1.1 x 1.21), so 31px of
    // spacing needs a 23.29px line-height.
    expect(Layout::lineHeight(31))->toBe(23.291)
        ->and(Layout::lineHeight(38))->toBe(28.55)
        // Roboto Mono is taller (1.1 x 1.319).
        ->and(Layout::lineHeight(31, 'mono'))->toBe(21.366);
});

it('positions a single line so its capitals start at the Figma cap top', function () {
    // Inter 48px: baseline at 1.1 x 0.969 x 48 below top, caps 0.7275 x 48 tall.
    expect(Layout::top(841, 48))->toBe(824.8)
        ->and(Layout::top(69, 36))->toBe(56.8)
        // Roboto Mono sits lower in its line box than Inter does.
        ->and(Layout::top(191, 48, null, 'mono'))->toBe(169.8);
});

it('keeps the baseline fixed when a fitted field shrinks', function () {
    $atFull = Layout::onBaseline(336, 30);
    $shrunk = Layout::onBaseline(336, 20);

    // Smaller text starts lower, so both sit on the same line above the divider.
    expect($shrunk)->toBeGreaterThan($atFull)
        ->and(round($atFull + 1.1 * 0.969 * 30, 1))->toBe(336.0)
        ->and(round($shrunk + 1.1 * 0.969 * 20, 1))->toBe(336.0);
});

it('uses the Figma variant\'s own measured tints for each design colour', function (string $accent, string $soft, string $strong) {
    $palette = CardPalette::fromAccent($accent);

    expect($palette->accent)->toBe($accent)
        ->and($palette->soft)->toBe($soft)
        ->and($palette->strong)->toBe($strong);
})->with([
    'Student' => ['#349AED', '#D9E5F7', '#2E73DA'],
    'Workers' => ['#27AE60', '#CFF7D3', '#219653'],
    'Magistr' => ['#3F2B96', '#E0DAF5', '#3F2B96'],
    'Doktarant' => ['#8B0000', '#F2D7D5', '#8B0000'],
    'Basqalar' => ['#C58A00', '#FFE89C', '#C58A00'],
]);

it('reproduces the design\'s warning-text colour from the square it sits beside', function () {
    // Figma's Student ink is #022354; the hue-preserving darkening lands a
    // shade away, and the same rule then covers the other four variants.
    expect(CardPalette::fromAccent('#349AED')->ink)->toBe('#032354');
});

it('still produces a usable palette for a colour the design does not have', function () {
    $palette = CardPalette::fromAccent('#2563eb');

    expect($palette->accent)->toBe('#2563EB')
        ->and($palette->soft)->toBe('#D6E1FB')
        // Three of the five variants leave the square at the accent, so that is
        // what an unknown colour gets too.
        ->and($palette->strong)->toBe('#2563EB')
        ->and($palette->ink)->toBe('#031C54')
        ->and($palette->pattern)->toBe('#D3DDF1');
});

it('falls back to the design\'s "Basqalar" orange when a type has no usable colour', function (?string $bad) {
    // Orange is the design's answer for anyone the five variants do not name,
    // which is also what the reader_types column defaults to.
    expect(CardPalette::FALLBACK)->toBe('#C58A00')
        ->and(CardPalette::fromAccent($bad)->accent)->toBe('#C58A00')
        ->and(CardPalette::fromAccent($bad)->soft)->toBe('#FFE89C');
})->with([null, '', 'blue', '#12345', '#GGGGGG']);

it('normalises accent case so the same colour always renders the same', function () {
    expect(CardPalette::fromAccent('#2563eb')->accent)->toBe('#2563EB');
});

it('keeps a short name at the design size and shrinks a long one to fit', function () {
    $fitter = new TextFitter;
    $medium = resource_path('fonts/Inter-Medium.ttf');

    expect($fitter->fit('Palensheyev', $medium, 298, 30, 18, 0.06))->toBe(30)
        ->and($fitter->fit('Muhammadrasulovich-Abdulazizxonov', $medium, 298, 30, 18, 0.06))->toBeLessThan(30);
});

it('never shrinks below the minimum, however long the text', function () {
    $fitter = new TextFitter;

    expect($fitter->fit(str_repeat('Veterinariya ', 20), resource_path('fonts/Inter-Medium.ttf'), 298, 30, 18, 0.06))->toBe(18);
});

it('measures what dompdf lays out: a fitted line really fits its box', function () {
    $fitter = new TextFitter;
    $font = resource_path('fonts/Inter-Medium.ttf');
    $text = 'Tólensheyevich-Abdullayev';

    $size = $fitter->fit($text, $font, 298, 30, 18, 0.06);

    expect($fitter->widthInEm($text, $font, 0.06) * $size)->toBeLessThanOrEqual(298.0);
});

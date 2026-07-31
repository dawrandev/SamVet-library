<?php

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Category;
use App\Models\Dissertation;
use App\Models\Language;

beforeEach(fn () => actingAsAdmin());

it('shows the "Yillik hisobot" widget on the dashboard', function () {
    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Yillik hisobot');
});

it('shows a friendly empty state and reports no years when no copy has an acquisition date', function () {
    $res = $this->get(route('admin.dashboard'));

    $res->assertOk()->assertSee('Hali kirish akti sanasi kiritilgan kitob nusxalari yo‘q.');

    $report = $res->viewData('annualReport');
    $empty = ['labels' => [], 'copies' => [], 'titles' => [], 'otherIndex' => null];
    expect($report['years'])->toBe([])
        ->and($report['category'])->toBe($empty)
        ->and($report['format'])->toBe($empty)
        ->and($report['language'])->toBe($empty);
});

it('zero-fills every year between the actual min and max acquisition years', function () {
    $book = Book::factory()->create();
    BookCopy::factory()->create(['book_id' => $book->id, 'acquisition_act_at' => '2023-03-01']);
    BookCopy::factory()->create(['book_id' => $book->id, 'acquisition_act_at' => '2026-11-01']);

    $report = $this->get(route('admin.dashboard'))->viewData('annualReport');

    expect($report['years'])->toBe([2023, 2024, 2025, 2026]);

    $printIdx = array_search(\App\Enums\BookFormat::Print->label(), $report['format']['labels']);
    expect($report['format']['copies'][$printIdx])->toBe([1, 0, 0, 1]);
});

it('rolls up a book acquired under a child category to its top-level parent', function () {
    $parent = Category::factory()->create(['name' => ['uz' => 'Sinov toifasi'], 'parent_id' => null]);
    $child = Category::factory()->create(['name' => ['uz' => 'Sinov bola toifasi'], 'parent_id' => $parent->id]);
    $book = Book::factory()->create();
    $book->categories()->attach($child->id);
    BookCopy::factory()->count(2)->create(['book_id' => $book->id, 'acquisition_act_at' => '2025-06-15']);

    $report = $this->get(route('admin.dashboard'))->viewData('annualReport');

    $idx = array_search('Sinov toifasi', $report['category']['labels']);
    $yearIdx = array_search(2025, $report['years']);

    expect($idx)->not->toBeFalse()
        ->and($report['category']['copies'][$idx][$yearIdx])->toBe(2)
        ->and($report['category']['titles'][$idx][$yearIdx])->toBe(1); // distinct book, not per-copy
});

it('folds a book with no category at all into a "Boshqa" segment instead of dropping it', function () {
    $book = Book::factory()->create();
    BookCopy::factory()->create(['book_id' => $book->id, 'acquisition_act_at' => '2025-01-10']);

    $report = $this->get(route('admin.dashboard'))->viewData('annualReport');

    $idx = array_search('Boshqa', $report['category']['labels']);
    $yearIdx = array_search(2025, $report['years']);

    expect($idx)->not->toBeFalse()
        ->and($report['category']['copies'][$idx][$yearIdx])->toBe(1);
});

it('keeps a real category literally named "Boshqa" distinct from the synthetic overflow segment', function () {
    // Regression: the overflow segment used to be identified by matching
    // the "Boshqa" label text, which would silently merge/miscolor it with
    // a real category that happens to share that name (a natural catch-all
    // name in Uzbek). It's now identified structurally by index instead.
    $real = Category::factory()->create(['name' => ['uz' => 'Boshqa'], 'parent_id' => null]);
    $book = Book::factory()->create();
    $book->categories()->attach($real->id);
    BookCopy::factory()->create(['book_id' => $book->id, 'acquisition_act_at' => '2025-01-01']);

    $orphan = Book::factory()->create();
    BookCopy::factory()->create(['book_id' => $orphan->id, 'acquisition_act_at' => '2025-01-01']);

    $report = $this->get(route('admin.dashboard'))->viewData('annualReport');
    $yearIdx = array_search(2025, $report['years']);

    $boshqaIndexes = array_keys($report['category']['labels'], 'Boshqa', true);
    expect($boshqaIndexes)->toHaveCount(2) // the real category + the synthetic overflow, kept separate
        ->and($report['category']['otherIndex'])->toBe(max($boshqaIndexes)); // synthetic one is always appended last

    $totalBoshqaCopies = array_sum(array_map(fn ($i) => $report['category']['copies'][$i][$yearIdx], $boshqaIndexes));
    expect($totalBoshqaCopies)->toBe(2); // both books still counted, none dropped by the collision
});

it('counts a book under every top-level category it belongs to, not just one', function () {
    $catA = Category::factory()->create(['name' => ['uz' => 'Toifa A'], 'parent_id' => null]);
    $catB = Category::factory()->create(['name' => ['uz' => 'Toifa B'], 'parent_id' => null]);
    $book = Book::factory()->create();
    $book->categories()->attach([$catA->id, $catB->id]);
    BookCopy::factory()->create(['book_id' => $book->id, 'acquisition_act_at' => '2025-02-02']);

    $report = $this->get(route('admin.dashboard'))->viewData('annualReport');
    $yearIdx = array_search(2025, $report['years']);

    $idxA = array_search('Toifa A', $report['category']['labels']);
    $idxB = array_search('Toifa B', $report['category']['labels']);

    expect($report['category']['copies'][$idxA][$yearIdx])->toBe(1)
        ->and($report['category']['copies'][$idxB][$yearIdx])->toBe(1);
});

it('keeps only the top 6 languages by total, folding the rest and null-language books into "Boshqa"', function () {
    // 6 languages with 2 copies each stay named; a 7th with 1 copy, plus a
    // null-language book with 1 copy, are folded into a single "Boshqa".
    foreach (range(1, 6) as $i) {
        $lang = Language::factory()->create(['name' => ["uz" => "Til {$i}", 'ru' => "Til {$i}", 'kk' => "Til {$i}"]]);
        $book = Book::factory()->create(['language_id' => $lang->id]);
        BookCopy::factory()->count(2)->create(['book_id' => $book->id, 'acquisition_act_at' => '2025-04-01']);
    }

    $overflowLang = Language::factory()->create(['name' => ['uz' => 'Ortiqcha til', 'ru' => 'Ortiqcha til', 'kk' => 'Ortiqcha til']]);
    $overflowBook = Book::factory()->create(['language_id' => $overflowLang->id]);
    BookCopy::factory()->create(['book_id' => $overflowBook->id, 'acquisition_act_at' => '2025-04-01']);

    $nullLangBook = Book::factory()->create(['language_id' => null]);
    BookCopy::factory()->create(['book_id' => $nullLangBook->id, 'acquisition_act_at' => '2025-04-01']);

    $report = $this->get(route('admin.dashboard'))->viewData('annualReport');
    $yearIdx = array_search(2025, $report['years']);

    expect($report['language']['labels'])->toHaveCount(7); // 6 named + 1 "Boshqa"
    foreach (range(1, 6) as $i) {
        expect($report['language']['labels'])->toContain("Til {$i}");
    }
    expect($report['language']['labels'])->not->toContain('Ortiqcha til');

    $boshqaCount = array_count_values($report['language']['labels'])['Boshqa'] ?? 0;
    expect($boshqaCount)->toBe(1); // never two "Boshqa" segments

    $idx = array_search('Boshqa', $report['language']['labels']);
    expect($report['language']['copies'][$idx][$yearIdx])->toBe(2); // overflow lang (1) + null lang (1)
});

it('is book-only in scope — a Dissertation acquisition date has zero effect on the report', function () {
    Dissertation::factory()->create(['acquisition_act_at' => '2024-05-01']);

    $report = $this->get(route('admin.dashboard'))->viewData('annualReport');

    expect($report['years'])->toBe([]);
});

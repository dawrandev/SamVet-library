<?php

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Journal;
use App\Models\Subscription;

beforeEach(fn () => actingAsAdmin());

it('shows the "Yillik xarajatlar hisoboti" widget on the dashboard', function () {
    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Yillik xarajatlar hisoboti');
});

it('shows a friendly empty state when there is nothing to compute expenses from', function () {
    $res = $this->get(route('admin.dashboard'));

    $res->assertOk()->assertSee('Hali xarajat hisoblash uchun yetarli ma’lumot yo‘q.');

    $report = $res->viewData('annualExpenses');
    expect($report)->toBe(['years' => [], 'books' => [], 'subscriptions' => []]);
});

it('sums copy price by acquisition year, ignoring copies with no date or no price', function () {
    $priced = Book::factory()->create();
    BookCopy::factory()->create(['book_id' => $priced->id, 'price' => 30000, 'acquisition_act_at' => '2024-03-01']);
    BookCopy::factory()->create(['book_id' => $priced->id, 'price' => 30000, 'acquisition_act_at' => '2024-09-01']);

    $unpriced = Book::factory()->create();
    BookCopy::factory()->create(['book_id' => $unpriced->id, 'price' => null, 'acquisition_act_at' => '2024-05-01']);

    $noDate = Book::factory()->create();
    BookCopy::factory()->create(['book_id' => $noDate->id, 'price' => 99999, 'acquisition_act_at' => null]);

    $report = $this->get(route('admin.dashboard'))->viewData('annualExpenses');
    $yearIdx = array_search(2024, $report['years']);

    expect($yearIdx)->not->toBeFalse()
        ->and($report['books'][$yearIdx])->toBe(60000.0) // 2 copies x 30000, unpriced/undated copies contribute 0
        ->and($report['subscriptions'][$yearIdx])->toBe(0.0);
});

it('only counts budget-sourced subscriptions, never reader-paid ones', function () {
    $journal = Journal::factory()->create();

    Subscription::create([
        'source' => 'budget', 'journal_id' => $journal->id, 'year' => 2025,
        'start_month' => 1, 'end_month' => 12, 'amount' => 500000,
    ]);
    Subscription::create([
        'source' => 'reader', 'journal_id' => $journal->id, 'year' => 2025,
        'start_month' => 1, 'end_month' => 12, 'amount' => 200000,
    ]);

    $report = $this->get(route('admin.dashboard'))->viewData('annualExpenses');
    $yearIdx = array_search(2025, $report['years']);

    expect($yearIdx)->not->toBeFalse()
        ->and($report['subscriptions'][$yearIdx])->toBe(500000.0) // reader-paid 200000 excluded
        ->and($report['books'][$yearIdx])->toBe(0.0);
});

it('zero-fills every year across the combined book and subscription range', function () {
    $book = Book::factory()->create();
    BookCopy::factory()->create(['book_id' => $book->id, 'price' => 10000, 'acquisition_act_at' => '2022-01-01']);

    $journal = Journal::factory()->create();
    Subscription::create([
        'source' => 'budget', 'journal_id' => $journal->id, 'year' => 2025,
        'start_month' => 1, 'end_month' => 12, 'amount' => 100000,
    ]);

    $report = $this->get(route('admin.dashboard'))->viewData('annualExpenses');

    expect($report['years'])->toBe([2022, 2023, 2024, 2025])
        ->and($report['books'])->toBe([10000.0, 0.0, 0.0, 0.0])
        ->and($report['subscriptions'])->toBe([0.0, 0.0, 0.0, 100000.0]);
});

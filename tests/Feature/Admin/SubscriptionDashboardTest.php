<?php

use App\Models\DeliveryLocation;
use App\Models\Journal;
use App\Models\Reader;
use App\Models\Subscription;

beforeEach(fn () => actingAsAdmin());

// --- Dashboard aggregation (Table 1) ---
// Post branch/receipt upload and delivery-location CRUD tests already live in
// SubscriptionSourceTest.php — not duplicated here.

it('counts subscribers and marks covered months per journal for the given year', function () {
    $journal = Journal::factory()->create(['name' => 'Erkin Qoraqalpog‘iston']);
    $location = DeliveryLocation::factory()->create();

    // Three separate subscribers, overlapping/adjoining month ranges within 2026.
    foreach ([[1, 3], [4, 6], [4, 9]] as [$start, $end]) {
        Subscription::create([
            'reader_id' => Reader::factory()->create()->id,
            'source' => 'reader',
            'journal_id' => $journal->id,
            'delivery_location_id' => $location->id,
            'year' => 2026,
            'start_month' => $start,
            'end_month' => $end,
            'amount' => 100000,
        ]);
    }

    $coverage = app(\App\Services\SubscriptionService::class)->journalCoverage(2026);
    $row = collect($coverage)->firstWhere(fn ($r) => $r['journal']->id === $journal->id);

    expect($row)->not->toBeNull()
        ->and($row['count'])->toBe(3)
        // Jan-Mar: 1 subscriber; Apr-Jun: 2 (both ranges); Jul-Sep: 1 (only the second range); Oct-Dec: 0.
        ->and($row['months'][1])->toBe(1)
        ->and($row['months'][4])->toBe(2)
        ->and($row['months'][7])->toBe(1)
        ->and($row['months'][12])->toBe(0)
        // 9 of 12 months covered = 75%.
        ->and($row['percentage'])->toBe(75);
});

it('excludes journals with no subscriptions that year', function () {
    Journal::factory()->create(['name' => 'Obunasiz jurnal']);

    $coverage = app(\App\Services\SubscriptionService::class)->journalCoverage(2026);

    expect(collect($coverage)->pluck('journal.name'))->not->toContain('Obunasiz jurnal');
});

it('shows the dashboard page with month columns', function () {
    $journal = Journal::factory()->create(['name' => 'Dashboard sinov jurnali']);
    $location = DeliveryLocation::factory()->create();

    Subscription::create([
        'reader_id' => Reader::factory()->create()->id,
        'source' => 'reader',
        'journal_id' => $journal->id,
        'delivery_location_id' => $location->id,
        'year' => 2026,
        'start_month' => 1,
        'end_month' => 3,
        'amount' => 100000,
    ]);

    $this->get(route('admin.subscriptions.dashboard', ['year' => 2026]))
        ->assertOk()
        ->assertSee('Dashboard sinov jurnali')
        ->assertSee('25%'); // 3 of 12 months
});

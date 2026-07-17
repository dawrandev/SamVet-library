<?php

use App\Models\DeliveryLocation;
use App\Models\Journal;
use App\Models\Reader;
use App\Models\Subscription;

beforeEach(fn () => actingAsAdmin());

// --- DeliveryLocation lookup CRUD ---

it('creates a delivery location', function () {
    $this->post(route('admin.lookups.delivery-locations.store'), [
        'name' => 'SamMVShBU NF',
    ])->assertRedirect(route('admin.lookups.delivery-locations.index'));

    expect(DeliveryLocation::where('name', 'SamMVShBU NF')->exists())->toBeTrue();
});

it('deletes an unused delivery location', function () {
    $location = DeliveryLocation::factory()->create();

    $this->delete(route('admin.lookups.delivery-locations.destroy', $location))->assertRedirect();

    $this->assertDatabaseMissing('delivery_locations', ['id' => $location->id]);
});

// --- Required on subscription create/update ---

it('rejects a subscription with no delivery location', function () {
    $reader = Reader::factory()->create();
    $journal = Journal::factory()->create();

    $this->from(route('admin.subscriptions.index'))
        ->post(route('admin.subscriptions.store'), [
            'source' => 'reader',
            'reader_id' => $reader->id,
            'journal_id' => $journal->id,
            'year' => 2026,
            'start_month' => 1,
            'end_month' => 6,
            'amount' => 150000,
        ])
        ->assertSessionHasErrors('delivery_location_id');

    expect(Subscription::count())->toBe(0);
});

it('stores the chosen delivery location on the subscription', function () {
    $reader = Reader::factory()->create();
    $journal = Journal::factory()->create();
    $location = DeliveryLocation::factory()->create(['name' => 'Bosh kutubxona']);

    $this->post(route('admin.subscriptions.store'), [
        'source' => 'reader',
        'reader_id' => $reader->id,
        'journal_id' => $journal->id,
        'delivery_location_id' => $location->id,
        'year' => 2026,
        'start_month' => 1,
        'end_month' => 6,
        'amount' => 150000,
    ])->assertRedirect();

    $subscription = Subscription::first();
    expect($subscription->deliveryLocation->name)->toBe('Bosh kutubxona');
});

// --- Dashboard aggregation (Table 1) ---

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

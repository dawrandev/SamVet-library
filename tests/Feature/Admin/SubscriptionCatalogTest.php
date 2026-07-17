<?php

use App\Models\DeliveryLocation;
use App\Models\Journal;
use App\Models\PostBranch;
use App\Models\Reader;
use App\Models\Subscription;
use App\Models\SubscriptionCatalog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => actingAsAdmin());

/**
 * @return array<string, mixed>
 */
$catalogPayload = function (Journal $journal, DeliveryLocation $location, Reader $reader, int $startMonth, int $endMonth): array {
    return [
        'source' => 'reader',
        'reader_id' => $reader->id,
        'journal_id' => $journal->id,
        'delivery_location_id' => $location->id,
        'year' => 2027,
        'start_month' => $startMonth,
        'end_month' => $endMonth,
        'amount' => 999999, // deliberately wrong — must be ignored and recomputed
    ];
};

// --- SubscriptionCatalog CRUD ---

it('adds a journal to a year’s catalog', function () {
    $journal = Journal::factory()->create();

    $this->post(route('admin.subscription-catalog.store'), [
        'year' => 2027,
        'journal_id' => $journal->id,
        'annual_price' => 1800000,
        'is_selected' => '1',
    ])->assertRedirect();

    $entry = SubscriptionCatalog::first();
    expect($entry)->not->toBeNull()
        ->and((float) $entry->annual_price)->toBe(1800000.0)
        ->and($entry->is_selected)->toBeTrue();
});

it('rejects a duplicate journal for the same year', function () {
    $journal = Journal::factory()->create();
    SubscriptionCatalog::create(['year' => 2027, 'journal_id' => $journal->id, 'annual_price' => 1000000, 'is_selected' => true]);

    $this->from(route('admin.subscription-catalog.index'))
        ->post(route('admin.subscription-catalog.store'), [
            'year' => 2027,
            'journal_id' => $journal->id,
            'annual_price' => 2000000,
        ])
        ->assertSessionHasErrors('journal_id');
});

it('toggles is_selected without touching the price', function () {
    $entry = SubscriptionCatalog::factory()->create(['is_selected' => false, 'annual_price' => 500000]);

    $this->put(route('admin.subscription-catalog.update', $entry), [
        'year' => $entry->year,
        'journal_id' => $entry->journal_id,
        'annual_price' => $entry->annual_price,
        'is_selected' => '1',
    ])->assertRedirect();

    $entry->refresh();
    expect($entry->is_selected)->toBeTrue()
        ->and((float) $entry->annual_price)->toBe(500000.0);
});

it('deletes a catalog entry', function () {
    $entry = SubscriptionCatalog::factory()->create();

    $this->delete(route('admin.subscription-catalog.destroy', $entry))->assertRedirect();

    $this->assertDatabaseMissing('subscription_catalogs', ['id' => $entry->id]);
});

// --- Catalog-driven subscriptions (2027+) ---

it('rejects a journal that is not in the year’s shortlist', function () use ($catalogPayload) {
    $journal = Journal::factory()->create();
    $reader = Reader::factory()->create();
    $location = DeliveryLocation::factory()->create();

    $this->from(route('admin.subscriptions.index'))
        ->post(route('admin.subscriptions.store'), $catalogPayload($journal, $location, $reader, 1, 3))
        ->assertSessionHasErrors('journal_id');

    expect(Subscription::count())->toBe(0);
});

it('computes the amount from the catalog’s annual price, ignoring the submitted amount', function () use ($catalogPayload) {
    $journal = Journal::factory()->create();
    SubscriptionCatalog::create(['year' => 2027, 'journal_id' => $journal->id, 'annual_price' => 1200000, 'is_selected' => true]);
    $reader = Reader::factory()->create();
    $location = DeliveryLocation::factory()->create();

    $this->post(route('admin.subscriptions.store'), $catalogPayload($journal, $location, $reader, 1, 3))
        ->assertRedirect();

    $subscription = Subscription::first();
    // 1,200,000 / 12 * 3 months = 300,000 — not the bogus 999,999 that was submitted.
    expect((float) $subscription->amount)->toBe(300000.0);
});

it('requires the first subscription of the year to start in January', function () use ($catalogPayload) {
    $journal = Journal::factory()->create();
    SubscriptionCatalog::create(['year' => 2027, 'journal_id' => $journal->id, 'annual_price' => 1200000, 'is_selected' => true]);
    $reader = Reader::factory()->create();
    $location = DeliveryLocation::factory()->create();

    $this->from(route('admin.subscriptions.index'))
        ->post(route('admin.subscriptions.store'), $catalogPayload($journal, $location, $reader, 4, 6))
        ->assertSessionHasErrors('start_month');

    expect(Subscription::count())->toBe(0);
});

it('requires the next period to continue immediately after the previous one, no gap', function () use ($catalogPayload) {
    $journal = Journal::factory()->create();
    SubscriptionCatalog::create(['year' => 2027, 'journal_id' => $journal->id, 'annual_price' => 1200000, 'is_selected' => true]);
    $reader = Reader::factory()->create();
    $location = DeliveryLocation::factory()->create();

    $this->post(route('admin.subscriptions.store'), $catalogPayload($journal, $location, $reader, 1, 3))
        ->assertRedirect();

    // Skipping straight to July (leaving April-June uncovered) must be rejected.
    $this->from(route('admin.subscriptions.index'))
        ->post(route('admin.subscriptions.store'), $catalogPayload($journal, $location, $reader, 7, 9))
        ->assertSessionHasErrors('start_month');

    expect(Subscription::count())->toBe(1);
});

it('rejects re-subscribing to months already covered', function () use ($catalogPayload) {
    $journal = Journal::factory()->create();
    SubscriptionCatalog::create(['year' => 2027, 'journal_id' => $journal->id, 'annual_price' => 1200000, 'is_selected' => true]);
    $reader = Reader::factory()->create();
    $location = DeliveryLocation::factory()->create();

    $this->post(route('admin.subscriptions.store'), $catalogPayload($journal, $location, $reader, 1, 6))
        ->assertRedirect();

    // Apr-Jun overlaps the existing Jan-Jun period.
    $this->from(route('admin.subscriptions.index'))
        ->post(route('admin.subscriptions.store'), $catalogPayload($journal, $location, $reader, 4, 6))
        ->assertSessionHasErrors('start_month');

    expect(Subscription::count())->toBe(1);
});

it('allows the next consecutive period once the previous one is on record', function () use ($catalogPayload) {
    $journal = Journal::factory()->create();
    SubscriptionCatalog::create(['year' => 2027, 'journal_id' => $journal->id, 'annual_price' => 1200000, 'is_selected' => true]);
    $reader = Reader::factory()->create();
    $location = DeliveryLocation::factory()->create();

    $this->post(route('admin.subscriptions.store'), $catalogPayload($journal, $location, $reader, 1, 3))
        ->assertRedirect();

    $this->post(route('admin.subscriptions.store'), $catalogPayload($journal, $location, $reader, 4, 6))
        ->assertRedirect();

    expect(Subscription::where('reader_id', $reader->id)->count())->toBe(2);
});

it('does not enforce the shortlist or sequential rules for years before 2027', function () {
    $journal = Journal::factory()->create(); // no catalog entry at all
    $reader = Reader::factory()->create();
    $location = DeliveryLocation::factory()->create();

    $this->post(route('admin.subscriptions.store'), [
        'source' => 'reader',
        'reader_id' => $reader->id,
        'journal_id' => $journal->id,
        'delivery_location_id' => $location->id,
        'year' => 2026,
        'start_month' => 4, // would be rejected under the 2027+ rule — must pass here
        'end_month' => 6,
        'amount' => 150000,
    ])->assertRedirect();

    $subscription = Subscription::first();
    expect($subscription)->not->toBeNull()
        ->and((float) $subscription->amount)->toBe(150000.0);
});

// --- Post branch + receipt upload ---

it('stores the chosen post branch', function () use ($catalogPayload) {
    $journal = Journal::factory()->create();
    SubscriptionCatalog::create(['year' => 2027, 'journal_id' => $journal->id, 'annual_price' => 1200000, 'is_selected' => true]);
    $reader = Reader::factory()->create();
    $location = DeliveryLocation::factory()->create();
    $branch = PostBranch::factory()->create(['name' => 'Nukus pochtasi']);

    $payload = $catalogPayload($journal, $location, $reader, 1, 3);
    $payload['post_branch_id'] = $branch->id;

    $this->post(route('admin.subscriptions.store'), $payload)->assertRedirect();

    expect(Subscription::first()->postBranch->name)->toBe('Nukus pochtasi');
});

it('uploads and streams the payment receipt', function () use ($catalogPayload) {
    Storage::fake('local');

    $journal = Journal::factory()->create();
    SubscriptionCatalog::create(['year' => 2027, 'journal_id' => $journal->id, 'annual_price' => 1200000, 'is_selected' => true]);
    $reader = Reader::factory()->create();
    $location = DeliveryLocation::factory()->create();

    $payload = $catalogPayload($journal, $location, $reader, 1, 3);
    $payload['receipt_file'] = UploadedFile::fake()->image('kvitansiya.jpg');

    $this->post(route('admin.subscriptions.store'), $payload)->assertRedirect();

    $subscription = Subscription::first();
    expect($subscription->receipt_file)->not->toBeNull();
    Storage::disk('local')->assertExists($subscription->receipt_file);

    $this->get(route('admin.subscriptions.receipt', $subscription))->assertOk();
});

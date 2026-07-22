<?php

use App\Enums\SubscriptionSource;
use App\Models\DeliveryLocation;
use App\Models\Journal;
use App\Models\PostBranch;
use App\Models\Reader;
use App\Models\Subscription;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => actingAsAdmin());

it('creates a reader-funded subscription', function () {
    $reader = Reader::factory()->create();
    $journal = Journal::factory()->create();
    $location = DeliveryLocation::factory()->create();

    $this->post(route('admin.subscriptions.store'), [
        'source' => 'reader',
        'reader_id' => $reader->id,
        'journal_id' => $journal->id,
        'delivery_location_id' => $location->id,
        'year' => 2026,
        'start_month' => 1,
        'end_month' => 6,
        'amount' => 150000,
    ])->assertRedirect(route('admin.subscriptions.index'));

    $subscription = Subscription::first();
    expect($subscription->source)->toBe(SubscriptionSource::Reader)
        ->and($subscription->reader_id)->toBe($reader->id);
});

it('creates a budget-funded subscription without a reader', function () {
    $journal = Journal::factory()->create();
    $location = DeliveryLocation::factory()->create();

    $this->post(route('admin.subscriptions.store'), [
        'source' => 'budget',
        'journal_id' => $journal->id,
        'delivery_location_id' => $location->id,
        'year' => 2026,
        'start_month' => 1,
        'end_month' => 12,
        'amount' => 500000,
    ])->assertRedirect(route('admin.subscriptions.index'));

    $subscription = Subscription::first();
    expect($subscription->source)->toBe(SubscriptionSource::Budget)
        ->and($subscription->reader_id)->toBeNull();
});

it('ignores a client-submitted reader_id when the source is budget', function () {
    $reader = Reader::factory()->create();
    $journal = Journal::factory()->create();
    $location = DeliveryLocation::factory()->create();

    $this->post(route('admin.subscriptions.store'), [
        'source' => 'budget',
        'reader_id' => $reader->id,
        'journal_id' => $journal->id,
        'delivery_location_id' => $location->id,
        'year' => 2026,
        'start_month' => 1,
        'end_month' => 12,
        'amount' => 500000,
    ])->assertRedirect(route('admin.subscriptions.index'));

    expect(Subscription::first()->reader_id)->toBeNull();
});

it('requires a reader when the source is reader', function () {
    $journal = Journal::factory()->create();

    $this->post(route('admin.subscriptions.store'), [
        'source' => 'reader',
        'journal_id' => $journal->id,
        'year' => 2026,
        'start_month' => 1,
        'end_month' => 12,
        'amount' => 500000,
    ])->assertSessionHasErrors('reader_id');

    expect(Subscription::count())->toBe(0);
});

it('requires a source', function () {
    $reader = Reader::factory()->create();
    $journal = Journal::factory()->create();

    $this->post(route('admin.subscriptions.store'), [
        'reader_id' => $reader->id,
        'journal_id' => $journal->id,
        'year' => 2026,
        'start_month' => 1,
        'end_month' => 12,
        'amount' => 500000,
    ])->assertSessionHasErrors('source');
});

// --- Post branch + receipt upload ---

it('stores the chosen post branch', function () {
    $reader = Reader::factory()->create();
    $journal = Journal::factory()->create();
    $location = DeliveryLocation::factory()->create();
    $branch = PostBranch::factory()->create(['name' => 'Nukus pochtasi']);

    $this->post(route('admin.subscriptions.store'), [
        'source' => 'reader',
        'reader_id' => $reader->id,
        'journal_id' => $journal->id,
        'delivery_location_id' => $location->id,
        'post_branch_id' => $branch->id,
        'year' => 2026,
        'start_month' => 1,
        'end_month' => 3,
        'amount' => 150000,
    ])->assertRedirect();

    expect(Subscription::first()->postBranch->name)->toBe('Nukus pochtasi');
});

it('uploads and streams the payment receipt', function () {
    Storage::fake('local');

    $reader = Reader::factory()->create();
    $journal = Journal::factory()->create();
    $location = DeliveryLocation::factory()->create();

    $this->post(route('admin.subscriptions.store'), [
        'source' => 'reader',
        'reader_id' => $reader->id,
        'journal_id' => $journal->id,
        'delivery_location_id' => $location->id,
        'year' => 2026,
        'start_month' => 1,
        'end_month' => 3,
        'amount' => 150000,
        'receipt_file' => UploadedFile::fake()->image('kvitansiya.jpg'),
    ])->assertRedirect();

    $subscription = Subscription::first();
    expect($subscription->receipt_file)->not->toBeNull();
    Storage::disk('local')->assertExists($subscription->receipt_file);

    $this->get(route('admin.subscriptions.receipt', $subscription))->assertOk();
});

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

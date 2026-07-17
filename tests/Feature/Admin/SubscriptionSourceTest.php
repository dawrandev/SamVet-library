<?php

use App\Enums\SubscriptionSource;
use App\Models\DeliveryLocation;
use App\Models\Journal;
use App\Models\Reader;
use App\Models\Subscription;

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

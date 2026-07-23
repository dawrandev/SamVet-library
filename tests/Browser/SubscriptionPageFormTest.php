<?php

use App\Models\DeliveryLocation;
use App\Models\Journal;
use App\Models\Reader;
use App\Models\Subscription;
use App\Models\User;
use Laravel\Dusk\Browser;

/**
 * Subscriptions used to be created/edited in a modal, which fought CSS
 * height/scroll bugs across several attempts. Converted to a full page
 * (matching every other resource in this admin — Books, Readers, Journals,
 * ...), which sidesteps that whole bug class entirely.
 */
it('creates a subscription from the dedicated create page, no modal involved', function () {
    $admin = User::factory()->create();
    $journal = Journal::factory()->create();
    $location = DeliveryLocation::factory()->create();

    $this->browse(function (Browser $browser) use ($admin, $journal, $location) {
        $browser->resize(1280, 650)
            ->loginAs($admin)
            ->visit('/admin/subscriptions')
            ->clickLink('Yangi obuna')
            ->waitForLocation('/admin/subscriptions/create')
            ->assertSee('Yangi obuna')
            // The radio itself is visually hidden (sr-only) — click its label, matching how a real user picks it.
            ->click('label:has(input[value="budget"])')
            ->select('journal_id', (string) $journal->id)
            ->select('delivery_location_id', (string) $location->id)
            ->type('amount', '150000');

        // The Saqlash button is a normal page element — the browser's own
        // scroll handles reaching it, no custom modal scroll container needed.
        $browser->script("document.getElementById('journal_id').scrollIntoView()");
        $browser->press('Saqlash')
            ->waitForText('Obuna qo‘shildi', 10);
    });

    expect(Subscription::where('journal_id', $journal->id)->exists())->toBeTrue();
});

it('pre-fills the edit page with the subscription’s current values', function () {
    $admin = User::factory()->create();
    $reader = Reader::factory()->create();
    $journal = Journal::factory()->create();
    $location = DeliveryLocation::factory()->create();
    $subscription = Subscription::create([
        'reader_id' => $reader->id,
        'source' => 'reader',
        'journal_id' => $journal->id,
        'delivery_location_id' => $location->id,
        'year' => 2026,
        'start_month' => 1,
        'end_month' => 6,
        'amount' => 200000,
    ]);

    $this->browse(function (Browser $browser) use ($admin, $subscription) {
        $browser->loginAs($admin)
            ->visit('/admin/subscriptions/'.$subscription->id.'/edit')
            ->assertSee('Obunani tahrirlash')
            ->assertInputValue('year', '2026')
            ->assertInputValue('amount', '200000.00');
    });
});

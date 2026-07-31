<?php

use App\Models\Journal;
use App\Models\JournalIssue;

it('shows the "Yili" (since year) row on a journal page', function () {
    $journal = Journal::factory()->create();
    JournalIssue::factory()->create(['journal_id' => $journal->id, 'year' => 2019]);

    $this->get(route('journal.show', $journal->slug))
        ->assertOk()
        ->assertSee('Yili')
        ->assertSee('2019 yildan');
});

it('hides the "Yili" (since year) row on a newspaper page', function () {
    // Librarian request: newspapers don't carry a meaningful "since year"
    // the way journals do, so this "Nashr ma'lumotlari" table row is
    // newspaper-specific hidden. The hero caption's own "X yildan" text
    // (a separate, unrelated bit of periodicity copy) is untouched.
    $newspaper = Journal::factory()->newspaper()->create();
    JournalIssue::factory()->create(['journal_id' => $newspaper->id, 'year' => 2019]);

    $this->get(route('journal.show', $newspaper->slug))
        ->assertOk()
        ->assertDontSee('Yili');
});

<?php

use App\Models\Journal;
use App\Models\JournalCopy;
use App\Models\JournalIssue;

beforeEach(fn () => actingAsAdmin());

it('requires an inventory number for a journal copy', function () {
    $journal = Journal::factory()->create(['kind' => 'journal']);
    $issue = JournalIssue::factory()->create(['journal_id' => $journal->id]);

    $this->from(route('admin.journals.issues.show', [$journal, $issue]))
        ->post(route('admin.journal-issues.copies.store', $issue), [
            '_copy_form' => 'store',
            'status' => 'available',
        ])
        ->assertSessionHasErrors('inventory_number');
});

it('does not require an inventory number for a newspaper copy', function () {
    $journal = Journal::factory()->newspaper()->create();
    $issue = JournalIssue::factory()->create(['journal_id' => $journal->id]);

    $this->post(route('admin.journal-issues.copies.store', $issue), [
        '_copy_form' => 'store',
        'status' => 'available',
    ])->assertRedirect();

    expect(JournalCopy::where('journal_issue_id', $issue->id)->first())
        ->not->toBeNull()
        ->inventory_number->toBeNull();
});

it('shows a dash for a newspaper copy with no inventory number', function () {
    $journal = Journal::factory()->newspaper()->create();
    $issue = JournalIssue::factory()->create(['journal_id' => $journal->id]);
    JournalCopy::factory()->create(['journal_issue_id' => $issue->id, 'inventory_number' => null]);

    $this->get(route('admin.journals.issues.show', [$journal, $issue]))
        ->assertOk()
        ->assertSee('—');
});

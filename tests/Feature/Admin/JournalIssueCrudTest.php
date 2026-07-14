<?php

use App\Models\Journal;
use App\Models\JournalIssue;

beforeEach(fn () => actingAsAdmin());

it('creates a journal issue with an exact issue date', function () {
    $journal = Journal::factory()->create();

    $this->post(route('admin.journals.issues.store', $journal), [
        'year' => 2025,
        'issue_date' => '2025-03-15',
        'issue_number' => '5-son',
    ])->assertRedirect();

    $issue = JournalIssue::where('journal_id', $journal->id)->firstWhere('issue_number', '5-son');
    expect($issue)->not->toBeNull()
        ->and($issue->issue_date->format('Y-m-d'))->toBe('2025-03-15');
});

it('allows an issue without a specific date (year only)', function () {
    $journal = Journal::factory()->create();

    $this->post(route('admin.journals.issues.store', $journal), [
        'year' => 2025,
        'issue_number' => '6-son',
    ])->assertRedirect();

    $issue = JournalIssue::where('journal_id', $journal->id)->firstWhere('issue_number', '6-son');
    expect($issue->issue_date)->toBeNull();
});

it('rejects an invalid issue date', function () {
    $journal = Journal::factory()->create();

    $this->from(route('admin.journals.show', $journal))
        ->post(route('admin.journals.issues.store', $journal), [
            'year' => 2025,
            'issue_date' => 'not-a-date',
            'issue_number' => '7-son',
        ])
        ->assertSessionHasErrors('issue_date');
});

it('updates a journal issue’s date', function () {
    $issue = JournalIssue::factory()->create(['issue_date' => '2024-01-01']);

    $this->put(route('admin.journals.issues.update', [$issue->journal, $issue]), [
        'year' => $issue->year,
        'issue_date' => '2024-06-20',
        'issue_number' => $issue->issue_number,
    ])->assertRedirect();

    expect($issue->fresh()->issue_date->format('Y-m-d'))->toBe('2024-06-20');
});

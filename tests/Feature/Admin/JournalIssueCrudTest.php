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

it('shows the parent journal/newspaper\'s own info on the issue page (self-contained view)', function () {
    $journal = Journal::factory()->create([
        'name' => 'Nukus tongi', 'kind' => 'newspaper', 'issn' => '1111-2222',
    ]);
    $issue = JournalIssue::factory()->create([
        'journal_id' => $journal->id, 'issue_number' => '9-son', 'issue_date' => '2025-02-01',
    ]);

    $this->get(route('admin.journals.issues.show', [$journal, $issue]))
        ->assertOk()
        ->assertSee('Gazeta haqida ma’lumot') // newspaper kind → "Gazeta", not "Jurnal"
        ->assertSee('Nukus tongi')
        ->assertSee('1111-2222')
        ->assertSee('Son haqida ma’lumot')
        ->assertSee('9-son')
        ->assertSee('01.02.2025');
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

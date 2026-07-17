<?php

use App\Models\Dissertation;
use App\Models\JournalIssue;
use Illuminate\Http\UploadedFile;

beforeEach(fn () => actingAsAdmin());

it('creates a dissertation linked to a journal issue', function () {
    $issue = JournalIssue::factory()->create();

    $this->post(route('admin.dissertations.store'), [
        'journal_issue_id' => $issue->id,
        'title' => 'Test dissertatsiya',
        'author' => 'N. Testov',
    ])->assertRedirect();

    $dissertation = Dissertation::firstWhere('title', 'Test dissertatsiya');
    expect($dissertation)->not->toBeNull()
        ->and($dissertation->journal_issue_id)->toBe($issue->id)
        ->and($dissertation->slug)->not->toBeEmpty();
});

it('requires a journal issue and title, but not an author', function () {
    $this->from(route('admin.dissertations.create'))
        ->post(route('admin.dissertations.store'), [])
        ->assertSessionHasErrors(['journal_issue_id', 'title'])
        ->assertSessionDoesntHaveErrors('author');
});

it('rejects a non-pdf file', function () {
    $issue = JournalIssue::factory()->create();

    $this->from(route('admin.dissertations.create'))
        ->post(route('admin.dissertations.store'), [
            'journal_issue_id' => $issue->id,
            'title' => 'X',
            'author' => 'Y',
            'electronic_file' => UploadedFile::fake()->create('note.txt', 10, 'text/plain'),
        ])
        ->assertSessionHasErrors('electronic_file');
});

it('deletes a dissertation', function () {
    $dissertation = Dissertation::factory()->create();

    $this->delete(route('admin.dissertations.destroy', $dissertation))->assertRedirect();

    $this->assertDatabaseMissing('dissertations', ['id' => $dissertation->id]);
});

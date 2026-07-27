<?php

use App\Models\Journal;
use App\Models\JournalIssue;
use Illuminate\Support\Facades\Storage;

it('shows the "Sonni o‘qish" link on the journal page only when the issue has a file', function () {
    $journal = Journal::factory()->create();
    $withPdf = JournalIssue::factory()->for($journal)->create(['electronic_file' => 'journals/electronic/x.pdf']);
    $withoutPdf = JournalIssue::factory()->for($journal)->create(['electronic_file' => null]);

    $body = $this->get(route('journal.show', $journal->slug))->getContent();

    expect($body)->toContain(route('read.journal-issue', $withPdf->id))
        ->and($body)->not->toContain(route('read.journal-issue', $withoutPdf->id));
});

it('redirects a guest from the journal issue reader page to the reader login', function () {
    $issue = JournalIssue::factory()->create(['electronic_file' => 'journals/electronic/x.pdf']);

    $this->get(route('read.journal-issue', $issue->id))->assertRedirect(route('reader.login'));
});

it('lets a signed-in reader open the journal issue reader page', function () {
    actingAsReader();
    $issue = JournalIssue::factory()->create(['electronic_file' => 'journals/electronic/x.pdf']);

    $this->get(route('read.journal-issue', $issue->id))->assertOk();
});

it('streams the journal issue pdf inline, never as a download', function () {
    Storage::fake('local');
    $path = 'journals/electronic/x.pdf';
    Storage::disk('local')->put($path, '%PDF-1.7 fake');

    actingAsReader();
    $issue = JournalIssue::factory()->create(['electronic_file' => $path]);

    $res = $this->get(route('read.journal-issue.file', $issue->id));

    $res->assertOk();
    expect($res->headers->get('content-type'))->toContain('application/pdf')
        ->and($res->headers->get('content-disposition'))->toContain('inline')
        ->and($res->headers->get('content-disposition'))->not->toContain('attachment');
});

it('404s when a reader opens a journal issue that has no stored pdf', function () {
    actingAsReader();
    $issue = JournalIssue::factory()->create(['electronic_file' => null]);

    $this->get(route('read.journal-issue', $issue->id))->assertNotFound();
    $this->get(route('read.journal-issue.file', $issue->id))->assertNotFound();
});

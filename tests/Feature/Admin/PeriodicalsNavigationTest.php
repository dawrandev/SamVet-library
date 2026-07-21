<?php

use App\Models\Article;
use App\Models\Journal;
use App\Models\JournalIssue;

beforeEach(fn () => actingAsAdmin());

it('no longer shows separate Maqolalar/Gazeta maqolalari links in the sidebar', function () {
    $res = $this->get(route('admin.dashboard'));

    $res->assertOk()
        ->assertDontSee(route('admin.articles.index', ['kind' => 'journal']), false)
        ->assertDontSee(route('admin.articles.index', ['kind' => 'newspaper']), false)
        ->assertSee(route('admin.journals.index'), false);
});

it('shows an in-page tab nav linking journals and articles on the journals index', function () {
    $this->get(route('admin.journals.index'))
        ->assertSee('Davriy nashrlar')
        ->assertSee('Maqolalar')
        ->assertSee(route('admin.articles.index'), false);
});

it('shows an in-page tab nav linking journals and articles on the articles index', function () {
    $this->get(route('admin.articles.index'))
        ->assertSee('Davriy nashrlar')
        ->assertSee('Maqolalar')
        ->assertSee(route('admin.journals.index'), false);
});

it('lets the librarian filter articles by Nashr turi via a real select, defaulting to all', function () {
    $journal = Journal::factory()->create(['kind' => 'journal']);
    $newspaper = Journal::factory()->create(['kind' => 'newspaper']);
    $journalIssue = JournalIssue::factory()->create(['journal_id' => $journal->id]);
    $newspaperIssue = JournalIssue::factory()->create(['journal_id' => $newspaper->id]);

    Article::factory()->create(['journal_issue_id' => $journalIssue->id, 'title' => 'Jurnal maqolasi']);
    Article::factory()->create(['journal_issue_id' => $newspaperIssue->id, 'title' => 'Gazeta maqolasi']);

    // No kind param at all — "Barchasi" — both kinds show together.
    $this->get(route('admin.articles.index'))
        ->assertSee('<select name="kind"', false)
        ->assertSee('Jurnal maqolasi')
        ->assertSee('Gazeta maqolasi');
});
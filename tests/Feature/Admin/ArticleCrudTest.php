<?php

use App\Enums\ArticleCategory;
use App\Models\Article;
use App\Models\Journal;
use App\Models\JournalIssue;
use App\Models\ResourceField;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => actingAsAdmin());

it('creates an article linked to a journal issue', function () {
    $issue = JournalIssue::factory()->create();
    $field = ResourceField::factory()->create();

    $this->post(route('admin.articles.store'), [
        'journal_issue_id' => $issue->id,
        'title' => 'Chorva mollari kasalliklari',
        'author' => 'N. Testov, A. Ismoilov',
        'resource_field_id' => $field->id,
        'pages' => '12-18',
    ])->assertRedirect();

    $article = Article::firstWhere('title', 'Chorva mollari kasalliklari');
    expect($article)->not->toBeNull()
        ->and($article->journal_issue_id)->toBe($issue->id)
        ->and($article->slug)->not->toBeEmpty();
});

it('creates a newspaper article with an editorial category', function () {
    $issue = JournalIssue::factory()->create();

    $this->post(route('admin.articles.store'), [
        'journal_issue_id' => $issue->id,
        'title' => 'Filialimizda yangi laboratoriya ochildi',
        'author' => 'Xabarchi X.',
        'category' => ArticleCategory::AboutBranch->value,
    ])->assertRedirect();

    $article = Article::firstWhere('title', 'Filialimizda yangi laboratoriya ochildi');
    expect($article->category)->toBe(ArticleCategory::AboutBranch);
});

it('rejects an invalid category', function () {
    $issue = JournalIssue::factory()->create();

    $this->from(route('admin.articles.create'))
        ->post(route('admin.articles.store'), [
            'journal_issue_id' => $issue->id,
            'title' => 'X',
            'author' => 'Y',
            'category' => 'editorial_board', // not an ArticleCategory case
        ])
        ->assertSessionHasErrors('category');
});

it('requires a journal issue, title and author', function () {
    $this->from(route('admin.articles.create'))
        ->post(route('admin.articles.store'), [])
        ->assertSessionHasErrors(['journal_issue_id', 'title', 'author']);
});

it('rejects a non-pdf file', function () {
    $issue = JournalIssue::factory()->create();

    $this->from(route('admin.articles.create'))
        ->post(route('admin.articles.store'), [
            'journal_issue_id' => $issue->id,
            'title' => 'X',
            'author' => 'Y',
            'electronic_file' => UploadedFile::fake()->create('note.txt', 10, 'text/plain'),
        ])
        ->assertSessionHasErrors('electronic_file');
});

it('stores the pdf on the protected local disk', function () {
    Storage::fake('local');
    $issue = JournalIssue::factory()->create();

    $this->post(route('admin.articles.store'), [
        'journal_issue_id' => $issue->id,
        'title' => 'PDF maqola',
        'author' => 'Y',
        'electronic_file' => UploadedFile::fake()->create('article.pdf', 100, 'application/pdf'),
    ])->assertRedirect();

    $article = Article::firstWhere('title', 'PDF maqola');
    // Protected (not public) — served only through the auth'd stream controller.
    expect($article->electronic_file)->not->toBeNull();
    Storage::disk('local')->assertExists($article->electronic_file);
});

it('deletes an article', function () {
    $article = Article::factory()->create();

    $this->delete(route('admin.articles.destroy', $article))->assertRedirect();

    $this->assertDatabaseMissing('articles', ['id' => $article->id]);
});

it('separates journal articles from newspaper (gazeta) articles in the index (?kind=)', function () {
    $journal = Journal::factory()->create(['kind' => 'journal']);
    $newspaper = Journal::factory()->create(['kind' => 'newspaper']);
    $journalIssue = JournalIssue::factory()->create(['journal_id' => $journal->id]);
    $newspaperIssue = JournalIssue::factory()->create(['journal_id' => $newspaper->id]);

    Article::factory()->create(['journal_issue_id' => $journalIssue->id, 'title' => 'Jurnal maqolasi']);
    Article::factory()->create(['journal_issue_id' => $newspaperIssue->id, 'title' => 'Gazeta maqolasi']);

    $this->get(route('admin.articles.index', ['kind' => 'journal']))
        ->assertSee('Jurnal maqolasi')
        ->assertDontSee('Gazeta maqolasi')
        ->assertSee('Maqolalar');

    $this->get(route('admin.articles.index', ['kind' => 'newspaper']))
        ->assertSee('Gazeta maqolasi')
        ->assertDontSee('Jurnal maqolasi')
        ->assertSee('Gazeta maqolalari');
});

it('shows dynamic "Gazeta" labels on a newspaper article\'s show page', function () {
    $newspaper = Journal::factory()->create(['kind' => 'newspaper', 'name' => 'Kunlik xabar']);
    $issue = JournalIssue::factory()->create(['journal_id' => $newspaper->id]);
    $article = Article::factory()->create(['journal_issue_id' => $issue->id]);

    $this->get(route('admin.articles.show', $article))
        ->assertSee('Gazeta haqida ma’lumot')
        ->assertSee('Gazeta nomi')
        ->assertSee('Kunlik xabar')
        ->assertDontSee('Jurnal haqida ma’lumot');
});

it('restricts the journal-picker search to newspapers when creating via ?kind=newspaper', function () {
    Journal::factory()->create(['kind' => 'journal', 'name' => 'Ilmiy jurnal']);
    Journal::factory()->create(['kind' => 'newspaper', 'name' => 'Kunlik gazeta']);

    $response = $this->getJson(route('admin.journals.search', ['q' => '', 'kind' => 'newspaper']));

    $names = collect($response->json('data'))->pluck('name');
    expect($names)->toContain('Kunlik gazeta')
        ->and($names)->not->toContain('Ilmiy jurnal');
});

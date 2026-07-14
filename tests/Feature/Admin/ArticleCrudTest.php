<?php

use App\Enums\ArticleCategory;
use App\Models\Article;
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

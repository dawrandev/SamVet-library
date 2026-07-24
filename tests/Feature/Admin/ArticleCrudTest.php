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

it('requires a journal issue and title, but not an author', function () {
    $this->from(route('admin.articles.create'))
        ->post(route('admin.articles.store'), [])
        ->assertSessionHasErrors(['journal_issue_id', 'title'])
        ->assertSessionDoesntHaveErrors('author');
});

it('creates an article with no author at all', function () {
    $issue = JournalIssue::factory()->create();

    $this->post(route('admin.articles.store'), [
        'journal_issue_id' => $issue->id,
        'title' => 'Muallifsiz maqola',
    ])->assertRedirect();

    $article = Article::firstWhere('title', 'Muallifsiz maqola');
    expect($article)->not->toBeNull()
        ->and($article->author)->toBeNull();
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

it('creates a library-external article with a free-text journal name (no journal_issue_id)', function () {
    $field = ResourceField::factory()->create();

    $this->post(route('admin.articles.store'), [
        'external_journal_name' => 'Journal of Veterinary Science',
        'external_journal_year' => 2025,
        'title' => 'Xalqaro maqola',
        'author' => 'Prof. A. Aliyev',
        'resource_field_id' => $field->id,
    ])->assertRedirect();

    $article = Article::firstWhere('title', 'Xalqaro maqola');
    expect($article)->not->toBeNull()
        ->and($article->journal_issue_id)->toBeNull()
        ->and($article->external_journal_name)->toBe('Journal of Veterinary Science')
        ->and($article->external_journal_year)->toBe(2025)
        ->and($article->isExternal())->toBeTrue();
});

it('rejects an article with neither a journal issue nor an external journal name', function () {
    $this->from(route('admin.articles.create'))
        ->post(route('admin.articles.store'), [
            'title' => 'X',
            'author' => 'Y',
        ])
        ->assertSessionHasErrors(['journal_issue_id', 'external_journal_name']);
});

it('lists an external article under Maqolalar, never under Gazeta maqolalari', function () {
    Article::factory()->external()->create(['title' => 'Xalqaro maqola']);

    $this->get(route('admin.articles.index', ['kind' => 'journal']))
        ->assertSee('Xalqaro maqola')
        ->assertSee('Maqolalar');

    $this->get(route('admin.articles.index', ['kind' => 'newspaper']))
        ->assertDontSee('Xalqaro maqola');
});

it('shows the external journal name/year on the article show page instead of an empty panel', function () {
    $article = Article::factory()->external()->create([
        'external_journal_name' => 'Journal of Veterinary Science',
        'external_journal_year' => 2025,
    ]);

    $this->get(route('admin.articles.show', $article))
        ->assertSee('Tashqi jurnal haqida ma’lumot')
        ->assertSee('Journal of Veterinary Science')
        ->assertSee('2025')
        ->assertDontSee('Ma’lumot yo‘q');
});

it('renders the public article page for an external article without crashing', function () {
    $article = Article::factory()->external()->create(['title' => 'Xalqaro ochiq maqola']);

    $this->get(route('article.show', $article->slug))
        ->assertOk()
        ->assertSee('Xalqaro ochiq maqola');
});

it('restricts the journal-picker search to newspapers when creating via ?kind=newspaper', function () {
    Journal::factory()->create(['kind' => 'journal', 'name' => 'Ilmiy jurnal']);
    Journal::factory()->create(['kind' => 'newspaper', 'name' => 'Kunlik gazeta']);

    $response = $this->getJson(route('admin.journals.search', ['q' => '', 'kind' => 'newspaper']));

    $names = collect($response->json('data'))->pluck('name');
    expect($names)->toContain('Kunlik gazeta')
        ->and($names)->not->toContain('Ilmiy jurnal');
});

it('always shows both "Yangi maqola" and "Yangi gazeta maqolasi" buttons on the articles index, regardless of the current filter', function () {
    // Without this, a librarian landing on the unfiltered list only ever sees
    // "Yangi maqola" (which opens a journal-flavored form with no in-form way
    // to switch to newspaper mode) — she'd have no visible path to add a
    // gazeta article at all.
    $this->get(route('admin.articles.index'))
        ->assertSee(__('Yangi maqola'))
        ->assertSee(__('Yangi gazeta maqolasi'))
        ->assertSee(route('admin.articles.create', ['kind' => 'journal']), false)
        ->assertSee(route('admin.articles.create', ['kind' => 'newspaper']), false);

    $this->get(route('admin.articles.index', ['kind' => 'newspaper']))
        ->assertSee(__('Yangi maqola'))
        ->assertSee(__('Yangi gazeta maqolasi'));
});

<?php

use App\Models\Article;

/**
 * Articles were the one catalogued resource with no listing page of its own.
 * A journal's articles are reachable through its issue, but an external
 * article (journal_issue_id = null) had nothing linking to it: nine of them
 * sat on production, indexed and readable, and no visitor could arrive at one
 * without already knowing its slug.
 */
it('renders the public article catalog', function () {
    Article::factory()->create(['title' => 'Ochiq maqola']);

    $this->get(route('articles.index'))
        ->assertOk()
        ->assertSee('Ochiq maqola');
});

it('filters the article catalog by search', function () {
    Article::factory()->create(['title' => 'Veterinariya sohasida tadqiqot']);
    Article::factory()->create(['title' => 'Boshqa mavzu']);

    $this->get(route('articles.index', ['search' => 'Veterinariya']))
        ->assertOk()
        ->assertSee('Veterinariya sohasida tadqiqot')
        ->assertDontSee('Boshqa mavzu');
});

it('lists an external article, which has no issue page to appear on', function () {
    $article = Article::factory()->external()->create([
        'title' => 'Tashqi jurnaldagi maqola',
        'external_journal_name' => 'Acta Biologica Sibirica',
    ]);

    expect($article->journal_issue_id)->toBeNull();

    $this->get(route('articles.index'))
        ->assertOk()
        ->assertSee('Tashqi jurnaldagi maqola')
        // The journal it appeared in is free text on an external article, and
        // it is the only thing telling a reader where the work was published.
        ->assertSee('Acta Biologica Sibirica')
        ->assertSee(route('article.show', $article->slug), false);
});

it('names the journal a library-held article belongs to', function () {
    $article = Article::factory()->create(['title' => 'Jurnaldagi maqola']);

    $this->get(route('articles.index'))
        ->assertOk()
        ->assertSee($article->journalIssue->journal->name);
});

it('offers online reading only for an article that has a file', function () {
    Article::factory()->withPdf()->create(['title' => 'Toliq matnli maqola']);

    $this->get(route('articles.index'))
        ->assertOk()
        ->assertSee('Online o‘qish', false);
});

it('does not promise online reading for a bibliographic-only article', function () {
    // The reusable <x-site.article-row> prints the pill unconditionally, which
    // is why this listing writes its own row markup.
    Article::factory()->create(['title' => 'Faqat bibliografik yozuv', 'electronic_file' => null]);

    $this->get(route('articles.index'))
        ->assertOk()
        ->assertSee('Faqat bibliografik yozuv')
        ->assertDontSee('Online o‘qish', false);
});

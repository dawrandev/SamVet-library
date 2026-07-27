<?php

use App\Models\Article;
use Illuminate\Support\Facades\Storage;

it('shows the online-reading link on the article page only when a file is attached', function () {
    $withPdf = Article::factory()->withPdf()->create();
    $withoutPdf = Article::factory()->create();

    $this->get(route('article.show', $withPdf->slug))->assertSee(route('read.article', $withPdf->slug), false);
    $this->get(route('article.show', $withoutPdf->slug))->assertDontSee(route('read.article', $withoutPdf->slug), false);
});

it('redirects a guest from the article reader page to the reader login', function () {
    $article = Article::factory()->withPdf()->create();

    $this->get(route('read.article', $article->slug))->assertRedirect(route('reader.login'));
});

it('lets a signed-in reader open the article reader page', function () {
    actingAsReader();
    $article = Article::factory()->withPdf()->create();

    $this->get(route('read.article', $article->slug))->assertOk();
});

it('streams the article pdf inline, never as a download', function () {
    Storage::fake('local');
    $path = 'articles/electronic/x.pdf';
    Storage::disk('local')->put($path, '%PDF-1.7 fake');

    actingAsReader();
    $article = Article::factory()->withPdf($path)->create();

    $res = $this->get(route('read.article.file', $article->slug));

    $res->assertOk();
    expect($res->headers->get('content-type'))->toContain('application/pdf')
        ->and($res->headers->get('content-disposition'))->toContain('inline')
        ->and($res->headers->get('content-disposition'))->not->toContain('attachment');
});

it('404s when a reader opens an article that has no stored pdf', function () {
    actingAsReader();
    $article = Article::factory()->create();

    $this->get(route('read.article', $article->slug))->assertNotFound();
    $this->get(route('read.article.file', $article->slug))->assertNotFound();
});

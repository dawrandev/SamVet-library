<?php

use App\Models\Article;
use App\Models\OnlineRead;
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

it('logs an online read when a reader opens the article reader page', function () {
    $reader = actingAsReader();
    $article = Article::factory()->withPdf()->create();

    $this->get(route('read.article', $article->slug))->assertOk();

    $reading = OnlineRead::where('reader_id', $reader->id)
        ->where('readable_type', 'article')
        ->where('readable_id', $article->id)
        ->first();

    expect($reading)->not->toBeNull()
        ->and($reading->read_at)->not->toBeNull()
        ->and($reading->read_at->diffInSeconds(now()))->toBeLessThan(5);
});

/**
 * End to end, because the report came from the other end: articles were being
 * read on the site while the dashboard's readings table stayed empty. Nothing
 * downstream was broken — the reader action simply never called log(), so
 * there was no row for the dashboard to show.
 */
it('shows an article read on the admin dashboard', function () {
    actingAsReader(['full_name' => 'Qoraev Qori']);
    $article = Article::factory()->withPdf()->create(['title' => 'Veterinariya maqolasi']);

    $this->get(route('read.article', $article->slug))->assertOk();

    actingAsAdmin();

    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Qoraev Qori')
        ->assertSee('Veterinariya maqolasi')
        ->assertSee('Maqola');
});

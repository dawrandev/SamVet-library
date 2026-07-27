<?php

use App\Models\News;
use App\Models\NewsCategory;
use App\Services\Site\HomeService;

it('shows only "E‘lonlar"-category news in the hero slider', function () {
    $elonlar = NewsCategory::factory()->create(['name' => ['uz' => 'E‘lonlar']]);
    $tanlovlar = NewsCategory::factory()->create(['name' => ['uz' => 'Tanlovlar']]);

    $announcement = News::factory()->create(['news_category_id' => $elonlar->id, 'published_at' => now()->subDay()]);
    $contest = News::factory()->create(['news_category_id' => $tanlovlar->id, 'published_at' => now()->subDay()]);

    $data = app(HomeService::class)->homeData();

    expect($data['heroAnnouncements']->pluck('id'))->toContain($announcement->id)
        ->and($data['heroAnnouncements']->pluck('id'))->not->toContain($contest->id);
});

it('keeps every category in the full "latest news" section, unlike the hero slider', function () {
    $elonlar = NewsCategory::factory()->create(['name' => ['uz' => 'E‘lonlar']]);
    $tanlovlar = NewsCategory::factory()->create(['name' => ['uz' => 'Tanlovlar']]);

    News::factory()->create(['news_category_id' => $elonlar->id, 'published_at' => now()->subDay()]);
    $contest = News::factory()->create(['news_category_id' => $tanlovlar->id, 'published_at' => now()->subDay()]);

    $data = app(HomeService::class)->homeData();

    expect($data['latestNews']->pluck('id'))->toContain($contest->id);
});

it('renders only E‘lonlar items in the home page hero slider markup', function () {
    $elonlar = NewsCategory::factory()->create(['name' => ['uz' => 'E‘lonlar']]);
    $tanlovlar = NewsCategory::factory()->create(['name' => ['uz' => 'Tanlovlar']]);

    News::factory()->create(['news_category_id' => $elonlar->id, 'title' => ['uz' => 'Elon sarlavhasi'], 'published_at' => now()->subDay()]);
    News::factory()->create(['news_category_id' => $tanlovlar->id, 'title' => ['uz' => 'Tanlov sarlavhasi'], 'published_at' => now()->subDay()]);

    $body = $this->get(route('home'))->getContent();

    // Both titles appear somewhere on the page (the full news section still
    // shows the contest) — isolate the hero slider block specifically: it
    // starts at its own label and ends where the statistics band begins.
    $heroStart = strpos($body, 'So‘nggi e’lonlar');
    $heroEnd = strpos($body, 'Jami kitoblar', $heroStart);
    $heroMarkup = ($heroStart !== false && $heroEnd !== false)
        ? substr($body, $heroStart, $heroEnd - $heroStart)
        : '';

    expect($heroMarkup)->not->toBe('')
        ->and($heroMarkup)->toContain('Elon sarlavhasi')
        ->and($heroMarkup)->not->toContain('Tanlov sarlavhasi');
});

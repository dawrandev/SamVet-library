<?php

use App\Models\News;
use App\Models\NewsCategory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => actingAsAdmin());

it('creates a news item', function () {
    $category = NewsCategory::factory()->create();

    $this->post(route('admin.news.store'), [
        'title' => ['uz' => 'Yangi kitoblar keldi'],
        'body' => ['uz' => 'Kutubxonaga yangi adabiyotlar qo‘shildi.'],
        'news_category_id' => $category->id,
        'published_at' => '2026-07-11 10:00:00',
    ])->assertRedirect();

    $news = News::where('news_category_id', $category->id)->first();
    expect($news)->not->toBeNull()
        ->and($news->getTranslation('title', 'uz'))->toBe('Yangi kitoblar keldi')
        ->and($news->slug)->not->toBeEmpty();
});

it('requires a title, body and category', function () {
    $this->from(route('admin.news.create'))
        ->post(route('admin.news.store'), [])
        ->assertSessionHasErrors(['title', 'body', 'news_category_id']);
});

it('requires at least one language for the title', function () {
    $category = NewsCategory::factory()->create();

    // title/body arrays are present but every language is blank.
    $this->from(route('admin.news.create'))
        ->post(route('admin.news.store'), [
            'title' => ['uz' => '', 'ru' => '', 'kk' => ''],
            'body' => ['uz' => '', 'ru' => '', 'kk' => ''],
            'news_category_id' => $category->id,
        ])
        ->assertSessionHasErrors(['title.uz', 'body.uz']);
});

it('stores the cover on the public disk', function () {
    Storage::fake('public');
    $category = NewsCategory::factory()->create();

    $this->post(route('admin.news.store'), [
        'title' => ['uz' => 'Muqovali yangilik'],
        'body' => ['uz' => 'Matn.'],
        'news_category_id' => $category->id,
        'cover' => UploadedFile::fake()->image('cover.jpg', 800, 600),
    ])->assertRedirect();

    $news = News::where('news_category_id', $category->id)->first();
    expect($news->cover_image)->not->toBeNull();
    Storage::disk('public')->assertExists($news->cover_image);
});

it('rejects a non-image cover', function () {
    $category = NewsCategory::factory()->create();

    $this->from(route('admin.news.create'))
        ->post(route('admin.news.store'), [
            'title' => ['uz' => 'X'],
            'body' => ['uz' => 'Y'],
            'news_category_id' => $category->id,
            'cover' => UploadedFile::fake()->create('malware.svg', 5, 'image/svg+xml'),
        ])
        ->assertSessionHasErrors('cover');
});

it('deletes a news item', function () {
    $news = News::factory()->create();

    $this->delete(route('admin.news.destroy', $news))->assertRedirect();

    $this->assertDatabaseMissing('news', ['id' => $news->id]);
});

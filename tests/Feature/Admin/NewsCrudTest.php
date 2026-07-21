<?php

use App\Models\News;
use App\Models\NewsCategory;
use App\Models\NewsImage;
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

it('removes the cover image on update when remove_cover is set', function () {
    Storage::fake('public');
    $path = UploadedFile::fake()->image('cover.jpg')->store('news/covers', 'public');
    $news = News::factory()->create(['cover_image' => $path]);

    $this->put(route('admin.news.update', $news), [
        'title' => ['uz' => $news->getTranslation('title', 'uz')],
        'body' => ['uz' => $news->getTranslation('body', 'uz')],
        'news_category_id' => $news->news_category_id,
        'remove_cover' => '1',
    ])->assertRedirect();

    expect($news->fresh()->cover_image)->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

it('keeps the cover when remove_cover is not set', function () {
    Storage::fake('public');
    $path = UploadedFile::fake()->image('cover.jpg')->store('news/covers', 'public');
    $news = News::factory()->create(['cover_image' => $path]);

    $this->put(route('admin.news.update', $news), [
        'title' => ['uz' => $news->getTranslation('title', 'uz')],
        'body' => ['uz' => $news->getTranslation('body', 'uz')],
        'news_category_id' => $news->news_category_id,
    ])->assertRedirect();

    expect($news->fresh()->cover_image)->toBe($path);
    Storage::disk('public')->assertExists($path);
});

it('a new cover upload wins even if remove_cover is also sent', function () {
    Storage::fake('public');
    $oldPath = UploadedFile::fake()->image('old.jpg')->store('news/covers', 'public');
    $news = News::factory()->create(['cover_image' => $oldPath]);

    $this->put(route('admin.news.update', $news), [
        'title' => ['uz' => $news->getTranslation('title', 'uz')],
        'body' => ['uz' => $news->getTranslation('body', 'uz')],
        'news_category_id' => $news->news_category_id,
        'remove_cover' => '1',
        'cover' => UploadedFile::fake()->image('new.jpg'),
    ])->assertRedirect();

    expect($news->fresh()->cover_image)->not->toBeNull()->not->toBe($oldPath);
    Storage::disk('public')->assertMissing($oldPath);
});

it('removes a chosen gallery image on update', function () {
    Storage::fake('public');
    $news = News::factory()->create();
    $keepPath = UploadedFile::fake()->image('keep.jpg')->store('news/gallery', 'public');
    $removePath = UploadedFile::fake()->image('remove.jpg')->store('news/gallery', 'public');
    $keep = NewsImage::create(['news_id' => $news->id, 'path' => $keepPath, 'sort_order' => 1]);
    $remove = NewsImage::create(['news_id' => $news->id, 'path' => $removePath, 'sort_order' => 2]);

    $this->put(route('admin.news.update', $news), [
        'title' => ['uz' => $news->getTranslation('title', 'uz')],
        'body' => ['uz' => $news->getTranslation('body', 'uz')],
        'news_category_id' => $news->news_category_id,
        'remove_gallery_ids' => [$remove->id],
    ])->assertRedirect();

    $this->assertDatabaseMissing('news_images', ['id' => $remove->id]);
    $this->assertDatabaseHas('news_images', ['id' => $keep->id]);
    Storage::disk('public')->assertMissing($removePath);
    Storage::disk('public')->assertExists($keepPath);
});

it('does not let a gallery image be removed through a different news item', function () {
    Storage::fake('public');
    $newsA = News::factory()->create();
    $newsB = News::factory()->create();
    $path = UploadedFile::fake()->image('a.jpg')->store('news/gallery', 'public');
    $image = NewsImage::create(['news_id' => $newsA->id, 'path' => $path, 'sort_order' => 1]);

    $this->put(route('admin.news.update', $newsB), [
        'title' => ['uz' => $newsB->getTranslation('title', 'uz')],
        'body' => ['uz' => $newsB->getTranslation('body', 'uz')],
        'news_category_id' => $newsB->news_category_id,
        'remove_gallery_ids' => [$image->id],
    ])->assertRedirect();

    $this->assertDatabaseHas('news_images', ['id' => $image->id]);
    Storage::disk('public')->assertExists($path);
});

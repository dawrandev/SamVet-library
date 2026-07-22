<?php

use App\Models\Audiobook;
use App\Models\AudioTrack;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Category;
use App\Models\Journal;
use App\Models\News;
use App\Models\PublicationPlace;
use App\Models\Video;
use App\Models\VideoTrack;

it('renders the core public pages', function (string $path) {
    $this->get($path)->assertOk();
})->with([
    'home' => '/',
    'catalog' => '/katalog',
    'sections' => '/bolimlar',
    'periodicals' => '/jurnallar',
    'audiobooks' => '/audiokitoblar',
    'videos' => '/videolar',
    'statistics' => '/statistika',
    'news' => '/yangiliklar',
    'login' => '/kirish',
]);

it('shows a book detail page but hides admin-only data', function () {
    $book = Book::factory()->create(['title' => 'Ochiq kitob', 'print_run' => 7777]);
    BookCopy::factory()->create([
        'book_id' => $book->id,
        'inventory_number' => 'INV-SECRET',
        'price' => 88888,
    ]);

    $res = $this->get(route('book.show', $book->slug));

    $res->assertOk()->assertSee('Ochiq kitob');
    // Inventory number, price and print run are librarian-only.
    $res->assertDontSee('INV-SECRET')
        ->assertDontSee('88888')
        ->assertDontSee('7777');
    // The raw protected file path must never leak into the public HTML.
    $res->assertDontSee('books/electronic');
});

it('shows a book\'s parallel title and all its languages on the public detail page', function () {
    $uz = \App\Models\Language::factory()->create(['name' => 'Oʻzbek']);
    $ru = \App\Models\Language::factory()->create(['name' => 'Rus']);
    $book = Book::factory()->create([
        'title' => 'Veterinariya asoslari',
        'parallel_titles' => ['Основы ветеринарии'],
        'language_id' => $uz->id,
    ]);
    $book->languages()->sync([$uz->id, $ru->id]);

    $this->get(route('book.show', $book->slug))
        ->assertOk()
        ->assertSee('Основы ветеринарии')
        ->assertSee($uz->name)
        ->assertSee($ru->name);
});

it('shows a book tagged with a child category under its parent category name, not the child\'s', function () {
    $parent = Category::factory()->create(['name' => 'Ota kategoriya']);
    $child = Category::factory()->create(['name' => 'Yashirin submavzu', 'parent_id' => $parent->id]);
    $book = Book::factory()->create(['title' => 'Submavzuli kitob']);
    $book->categories()->attach($child->id);

    $res = $this->get(route('book.show', $book->slug));

    $res->assertOk()
        ->assertSee('Ota kategoriya')
        ->assertDontSee('Yashirin submavzu')
        ->assertSee(route('catalog', ['categories' => [$parent->id]]), false);
});

it('shows a journal detail page but hides library-internal fields', function () {
    $place = PublicationPlace::factory()->create(['name' => ['uz' => 'Maxfiy shahar']]);
    $journal = Journal::factory()->create([
        'name' => 'Ochiq jurnal',
        'index' => 'IDX-SECRET',
        'founder' => 'Maxfiy muassis',
        'publication_place_id' => $place->id,
        'periodicity_unit' => 'week',
    ]);

    $res = $this->get(route('journal.show', $journal->slug));

    $res->assertOk()->assertSee('Ochiq jurnal')
        ->assertSee('Haftalik');
    // Indeks, muassis and nashr joyi are library-internal — admin-only,
    // never shown on the public site.
    $res->assertDontSee('IDX-SECRET')
        ->assertDontSee('Maxfiy muassis')
        ->assertDontSee('Maxfiy shahar');
});

it('shows a newspaper detail page with its fixed newspaper_type label', function () {
    $newspaper = Journal::factory()->newspaper()->create([
        'name' => 'Ochiq gazeta',
        'newspaper_type' => 'spiritual_educational',
    ]);

    $this->get(route('journal.show', $newspaper->slug))
        ->assertOk()
        ->assertSee('Ochiq gazeta')
        ->assertSee('Ma’naviy-ma’rifiy gazeta');
});

it('shows an audiobook detail page with its track list, but no direct file link', function () {
    $audiobook = Audiobook::factory()->create(['name' => 'Ochiq audiokitob', 'author' => 'Ochiq muallif']);
    $track = AudioTrack::factory()->for($audiobook)->create(['title' => '1-qism', 'audio_file' => 'audiobooks/audio/secret.mp3']);

    $res = $this->get(route('audiobook.show', $audiobook->slug));

    $res->assertOk()
        ->assertSee('Ochiq audiokitob')
        ->assertSee('Ochiq muallif')
        ->assertSee('1-qism');
    // The raw protected file path must never leak into the public HTML.
    $res->assertDontSee($track->audio_file);
});

it('shows a video detail page with its track list, but no direct file link', function () {
    $video = Video::factory()->create(['name' => 'Ochiq video', 'author' => 'Ochiq muallif']);
    $track = VideoTrack::factory()->for($video)->create(['title' => '1-qism', 'video_file' => 'videos/video/secret.mp4']);

    $res = $this->get(route('video.show', $video->slug));

    $res->assertOk()
        ->assertSee('Ochiq video')
        ->assertSee('Ochiq muallif')
        ->assertSee('1-qism');
    // The raw protected file path must never leak into the public HTML.
    $res->assertDontSee($track->video_file);
});

it('shows a published news item and hides drafts', function () {
    $published = News::factory()->create();
    $draft = News::factory()->draft()->create();

    $this->get(route('news.index'))->assertOk();
    $this->get(route('news.show', $published->slug))->assertOk();
    // A draft (no published_at) is not publicly readable.
    $this->get(route('news.show', $draft->slug))->assertNotFound();
});

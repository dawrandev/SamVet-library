<?php

use App\Models\MenuItem;
use App\Models\Page;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => actingAsAdmin());

it('saves a title, body and gallery for a menu item', function () {
    Storage::fake('public');
    $menuItem = MenuItem::factory()->create();

    $this->put(route('admin.menu-items.page.update', $menuItem), [
        'title' => ['uz' => 'ARM nizomi'],
        'body' => ['uz' => '<p>Matn.</p>'],
        'gallery' => [
            UploadedFile::fake()->image('one.jpg'),
            UploadedFile::fake()->image('two.jpg'),
        ],
    ])->assertRedirect(route('admin.menu-items.index'));

    $page = Page::where('menu_item_id', $menuItem->id)->first();
    expect($page)->not->toBeNull()
        ->and($page->getTranslation('title', 'uz'))->toBe('ARM nizomi')
        ->and($page->images)->toHaveCount(2);

    foreach ($page->images as $image) {
        Storage::disk('public')->assertExists($image->path);
    }
});

it('appends new gallery images to existing ones on a later save', function () {
    Storage::fake('public');
    $menuItem = MenuItem::factory()->create();
    $page = Page::factory()->create(['menu_item_id' => $menuItem->id]);
    $page->images()->create(['path' => 'pages/gallery/existing.jpg', 'sort_order' => 1]);

    $this->put(route('admin.menu-items.page.update', $menuItem), [
        'gallery' => [UploadedFile::fake()->image('new.jpg')],
    ])->assertRedirect();

    expect($page->fresh()->images)->toHaveCount(2);
});

it('falls back to the menu item title on the public page when the page has none', function () {
    $menuItem = MenuItem::factory()->create(['title' => ['uz' => 'Foydalanish qoidasi']]);
    Page::factory()->create(['menu_item_id' => $menuItem->id, 'title' => [], 'body' => ['uz' => '<p>Qoidalar.</p>']]);

    $this->get(route('page.show', $menuItem))->assertSee('Foydalanish qoidasi');
});

it('prefers the page own title over the menu item title on the public page', function () {
    $menuItem = MenuItem::factory()->create(['title' => ['uz' => 'ARM nizomi']]);
    Page::factory()->create([
        'menu_item_id' => $menuItem->id,
        'title' => ['uz' => 'Axborot-resurs markazi nizomi'],
        'body' => ['uz' => '<p>Matn.</p>'],
    ]);

    $this->get(route('page.show', $menuItem))->assertSee('Axborot-resurs markazi nizomi');
});

it('shows an admin preview of the page content', function () {
    $menuItem = MenuItem::factory()->create();
    Page::factory()->create([
        'menu_item_id' => $menuItem->id,
        'title' => ['uz' => 'Sinov sahifasi'],
        'body' => ['uz' => '<p>Sinov matni.</p>'],
    ]);

    $this->get(route('admin.menu-items.page.show', $menuItem))
        ->assertOk()
        ->assertSee('Sinov sahifasi')
        ->assertSee('Sinov matni');
});

it('shows an empty state in the admin preview when no page content exists yet', function () {
    $menuItem = MenuItem::factory()->create();

    $this->get(route('admin.menu-items.page.show', $menuItem))
        ->assertOk()
        ->assertSee('hali kiritilmagan');
});

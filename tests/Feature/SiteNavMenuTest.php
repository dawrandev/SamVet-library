<?php

use App\Enums\MenuItemType;
use App\Models\MenuItem;
use App\Models\News;

it('renders the admin-built menu tree with its children in the navbar', function () {
    $about = MenuItem::factory()->dropdown()->create([
        'title' => ['uz' => 'ARM haqida'],
        'sort_order' => 1,
    ]);
    MenuItem::factory()->childOf($about)->create(['title' => ['uz' => 'ARM nizomi'], 'sort_order' => 1]);
    MenuItem::factory()->childOf($about)->create(['title' => ['uz' => 'Tuzilma'], 'sort_order' => 2]);

    $response = $this->get(route('home'));

    $response->assertOk()
        ->assertSee('ARM haqida')   // top-level dropdown label
        ->assertSee('ARM nizomi')   // child link
        ->assertSee('Tuzilma');
});

it('renders deeply nested menu items (grandchildren) too', function () {
    $about = MenuItem::factory()->dropdown()->create(['title' => ['uz' => 'ARM haqida']]);
    $child = MenuItem::factory()->childOf($about)->create(['title' => ['uz' => 'ARM nizomi']]);
    // A child inside a child — the admin allows arbitrary depth, so must the site.
    MenuItem::factory()->childOf($child)->create(['title' => ['uz' => 'Nizom ilovasi']]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('ARM haqida')
        ->assertSee('ARM nizomi')
        ->assertSee('Nizom ilovasi'); // grandchild must be present in the markup
});

it('hides inactive menu items and inactive children', function () {
    $active = MenuItem::factory()->dropdown()->create(['title' => ['uz' => 'Korinadigan menyu']]);
    MenuItem::factory()->childOf($active)->create(['title' => ['uz' => 'Korinadigan bola']]);
    MenuItem::factory()->childOf($active)->inactive()->create(['title' => ['uz' => 'Yashirin bola']]);

    MenuItem::factory()->inactive()->create(['title' => ['uz' => 'Yashirin menyu']]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Korinadigan menyu')
        ->assertSee('Korinadigan bola')
        ->assertDontSee('Yashirin bola')
        ->assertDontSee('Yashirin menyu');
});

it('always shows the fixed catalog anchor even with no menu configured', function () {
    // No MenuItem rows at all — the navbar must still render its core anchors.
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Elektron katalog')
        ->assertSee('Bosh sahifa');
});

it('renders a childless module-type menu item as a direct link, not an empty dropdown', function () {
    MenuItem::factory()->create([
        'title' => ['uz' => 'Yangilik'],
        'type' => MenuItemType::Module,
        'url' => 'news.index',
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Yangilik')
        ->assertSee(route('news.index'), false);
});

it('follows the Yangilik navbar link to the real news catalog, not an empty content-page placeholder', function () {
    MenuItem::factory()->create([
        'title' => ['uz' => 'Yangilik'],
        'type' => MenuItemType::Module,
        'url' => 'news.index',
    ]);
    News::factory()->create(['title' => ['uz' => 'Filialda yangi laboratoriya ochildi']]);

    $this->get(route('news.index'))
        ->assertOk()
        ->assertSee('Filialda yangi laboratoriya ochildi')
        ->assertDontSee('Sahifa matni tez orada qo‘shiladi');
});

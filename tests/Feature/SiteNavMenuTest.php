<?php

use App\Models\MenuItem;

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

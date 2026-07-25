<?php

use App\Models\Computer;
use App\Models\ComputerSession;

it('redirects a guest to the reader login', function () {
    $this->get(route('computers.index'))->assertRedirect(route('reader.login'));
});

it('lets a signed-in reader view the page', function () {
    actingAsReader();

    $this->get(route('computers.index'))->assertOk();
});

it('groups computers by room and shows each one\'s public status', function () {
    actingAsReader();
    Computer::factory()->create(['computer_number' => '1', 'status' => 'working', 'location' => 'reading_hall']);
    Computer::factory()->create(['computer_number' => '2', 'status' => 'broken', 'location' => 'electronic_library_hall']);

    $res = $this->get(route('computers.index'));

    $res->assertOk()
        ->assertSee('O‘qish zali')
        ->assertSee('Elektron kutubxona zali')
        ->assertSee('Bo‘sh')
        ->assertSee('Nosoz');
});

it('shows a computer as "Band" when an unfinished session occupies it', function () {
    actingAsReader();
    $computer = Computer::factory()->create(['computer_number' => '5', 'status' => 'working']);
    ComputerSession::factory()->create(['computer_id' => $computer->id]);

    $this->get(route('computers.index'))->assertOk()->assertSee('Band');
});

it('shows a computer as "Bo‘sh" once its session is finished', function () {
    actingAsReader();
    $computer = Computer::factory()->create(['computer_number' => '5', 'status' => 'working']);
    ComputerSession::factory()->finished()->create(['computer_id' => $computer->id]);

    $res = $this->get(route('computers.index'));

    // The status legend always shows the word "Band" once — only the status
    // pill on the tile itself should be absent once the session is finished.
    $res->assertOk();
    expect(substr_count($res->getContent(), 'Band'))->toBe(1);
});

it('excludes computers with no hand-out number', function () {
    actingAsReader();
    Computer::factory()->create(['computer_number' => null, 'model' => 'Xodim kompyuteri']);

    $this->get(route('computers.index'))->assertOk()->assertDontSee('Xodim kompyuteri');
});

it('hides the "Kompyuterlar" nav link from guests but shows it to signed-in readers', function () {
    $this->get(route('home'))->assertDontSee('Kompyuterlar');

    actingAsReader();
    $this->get(route('home'))->assertSee('Kompyuterlar');
});

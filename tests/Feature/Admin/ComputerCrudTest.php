<?php

use App\Enums\ComputerLocation;
use App\Models\Computer;

beforeEach(fn () => actingAsAdmin());

it('creates a computer with each of the 3 fixed location values', function (string $location) {
    $this->post(route('admin.computers.store'), [
        'model' => 'HP ProDesk 400 G7',
        'type' => 'desktop',
        'inventory_number' => 'KMP-'.$location,
        'status' => 'working',
        'location' => $location,
    ])->assertRedirect();

    $computer = Computer::firstWhere('inventory_number', 'KMP-'.$location);
    expect($computer)->not->toBeNull()
        ->and($computer->location)->toBe(ComputerLocation::from($location));
})->with([
    'book_lending',
    'reading_hall',
    'electronic_library_hall',
]);

it('rejects an invalid location value', function () {
    $this->from(route('admin.computers.create'))
        ->post(route('admin.computers.store'), [
            'model' => 'HP ProDesk 400 G7',
            'type' => 'desktop',
            'inventory_number' => 'KMP-BAD',
            'status' => 'working',
            'location' => 'not_a_real_location',
        ])
        ->assertSessionHasErrors('location');

    expect(Computer::firstWhere('inventory_number', 'KMP-BAD'))->toBeNull();
});

it('renders the fixed location select (not location_id) on the create form', function () {
    $this->get(route('admin.computers.create'))
        ->assertSee('<select name="location"', false)
        ->assertSee('Kitob berish bo‘limi')
        ->assertSee('O‘qish zali')
        ->assertSee('Elektron kutubxona zali')
        ->assertDontSee('<select name="location_id"', false)
        ->assertDontSee('Yangi joylashuv');
});

it('renders the fixed location select (not location_id) on the edit form', function () {
    $computer = Computer::factory()->create();

    $this->get(route('admin.computers.edit', $computer))
        ->assertSee('<select name="location"', false)
        ->assertDontSee('<select name="location_id"', false);
});

it('renders the fixed location select (not location_id) in the index page embedded modal', function () {
    $this->get(route('admin.computers.index'))
        ->assertSee('<select name="location"', false)
        ->assertSee('Kitob berish bo‘limi')
        ->assertSee('O‘qish zali')
        ->assertSee('Elektron kutubxona zali')
        ->assertDontSee('<select name="location_id"', false)
        ->assertDontSee('Yangi joylashuv');
});

it('shows the correct Uzbek label on the computer show page', function () {
    $computer = Computer::factory()->create(['location' => 'reading_hall']);

    $this->get(route('admin.computers.show', $computer))
        ->assertSee('O‘qish zali');
});

it('shows the correct Uzbek label in the index list table', function () {
    Computer::factory()->create(['model' => 'Lenovo M720', 'location' => 'electronic_library_hall']);

    $this->get(route('admin.computers.index'))
        ->assertSee('Lenovo M720')
        ->assertSee('Elektron kutubxona zali');
});

it('creates a computer with a computer_number distinct from the inventory number', function () {
    $this->post(route('admin.computers.store'), [
        'model' => 'HP ProDesk 400 G7',
        'type' => 'desktop',
        'inventory_number' => 'KMP-INV-1',
        'computer_number' => '7',
        'status' => 'working',
    ])->assertRedirect();

    $computer = Computer::firstWhere('inventory_number', 'KMP-INV-1');
    expect($computer->computer_number)->toBe('7');
});

it('renders the computer_number field on the create/edit forms', function () {
    $this->get(route('admin.computers.create'))
        ->assertSee('name="computer_number"', false);

    $computer = Computer::factory()->create();

    $this->get(route('admin.computers.edit', $computer))
        ->assertSee('name="computer_number"', false);
});

it('creates, updates and deletes a computer (happy path)', function () {
    $this->post(route('admin.computers.store'), [
        'model' => 'Dell OptiPlex 3080',
        'type' => 'desktop',
        'inventory_number' => 'KMP-HAPPY-1',
        'status' => 'working',
        'location' => 'book_lending',
    ])->assertRedirect();

    $computer = Computer::firstWhere('inventory_number', 'KMP-HAPPY-1');
    expect($computer)->not->toBeNull();

    $this->put(route('admin.computers.update', $computer), [
        'model' => 'Dell OptiPlex 3090',
        'type' => 'desktop',
        'inventory_number' => 'KMP-HAPPY-1',
        'status' => 'in_repair',
        'location' => 'reading_hall',
    ])->assertRedirect();

    $computer->refresh();
    expect($computer->model)->toBe('Dell OptiPlex 3090')
        ->and($computer->status->value)->toBe('in_repair')
        ->and($computer->location)->toBe(ComputerLocation::ReadingHall);

    $this->delete(route('admin.computers.destroy', $computer))->assertRedirect();

    $this->assertDatabaseMissing('computers', ['id' => $computer->id]);
});

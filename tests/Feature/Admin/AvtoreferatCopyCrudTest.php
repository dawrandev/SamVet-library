<?php

use App\Models\Avtoreferat;
use App\Models\AvtoreferatCopy;

beforeEach(fn () => actingAsAdmin());

it('creates a copy with an inventory number, condition and act fields', function () {
    $avtoreferat = Avtoreferat::factory()->create();

    $this->post(route('admin.avtoreferats.copies.store', $avtoreferat), [
        '_copy_form' => 'store',
        'inventory_number' => 'AVT-0001',
        'condition' => ['new'],
        'acquisition_act_number' => 'KA-0012',
        'acquisition_act_at' => '2024-03-10',
        'disposal_act_number' => 'CHA-0003',
        'disposal_act_at' => '2025-01-15',
    ])->assertRedirect(route('admin.avtoreferats.show', $avtoreferat));

    $copy = AvtoreferatCopy::firstWhere('inventory_number', 'AVT-0001');
    expect($copy)->not->toBeNull()
        ->and($copy->avtoreferat_id)->toBe($avtoreferat->id)
        ->and($copy->condition->first()->value)->toBe('new')
        ->and($copy->acquisition_act_number)->toBe('KA-0012')
        ->and($copy->acquisition_act_at->format('Y-m-d'))->toBe('2024-03-10')
        ->and($copy->disposal_act_number)->toBe('CHA-0003')
        ->and($copy->disposal_act_at->format('Y-m-d'))->toBe('2025-01-15');

    $this->get(route('admin.avtoreferats.show', $avtoreferat))
        ->assertSee('AVT-0001')
        ->assertSee('KA-0012')
        ->assertSee('10.03.2024')
        ->assertSee('CHA-0003')
        ->assertSee('15.01.2025');
});

it('saves a copy with no act info at all — only the inventory number is required', function () {
    $avtoreferat = Avtoreferat::factory()->create();

    $this->post(route('admin.avtoreferats.copies.store', $avtoreferat), [
        '_copy_form' => 'store',
        'inventory_number' => 'AVT-0002',
    ])->assertRedirect();

    $copy = AvtoreferatCopy::firstWhere('inventory_number', 'AVT-0002');
    expect($copy)->not->toBeNull()
        ->and($copy->acquisition_act_number)->toBeNull()
        ->and($copy->acquisition_act_at)->toBeNull()
        ->and($copy->disposal_act_number)->toBeNull()
        ->and($copy->disposal_act_at)->toBeNull();
});

it('requires an inventory number', function () {
    $avtoreferat = Avtoreferat::factory()->create();

    $this->from(route('admin.avtoreferats.show', $avtoreferat))
        ->post(route('admin.avtoreferats.copies.store', $avtoreferat), ['_copy_form' => 'store'])
        ->assertSessionHasErrors('inventory_number');
});

it('rejects a duplicate inventory number across different avtoreferats', function () {
    AvtoreferatCopy::factory()->create(['inventory_number' => 'AVT-DUP']);
    $avtoreferat = Avtoreferat::factory()->create();

    $this->from(route('admin.avtoreferats.show', $avtoreferat))
        ->post(route('admin.avtoreferats.copies.store', $avtoreferat), [
            '_copy_form' => 'store',
            'inventory_number' => 'AVT-DUP',
        ])
        ->assertSessionHasErrors('inventory_number');

    expect(AvtoreferatCopy::where('inventory_number', 'AVT-DUP')->count())->toBe(1);
});

it('updates a copy, excluding itself from the uniqueness check', function () {
    $avtoreferat = Avtoreferat::factory()->create();
    $copy = AvtoreferatCopy::factory()->create(['avtoreferat_id' => $avtoreferat->id, 'inventory_number' => 'AVT-0003']);

    $this->put(route('admin.avtoreferats.copies.update', [$avtoreferat, $copy]), [
        '_copy_form' => 'edit',
        'inventory_number' => 'AVT-0003', // unchanged — must not trip the uniqueness rule against itself
        'condition' => ['repaired'],
    ])->assertRedirect();

    expect($copy->fresh()->condition->first()->value)->toBe('repaired');
});

it('deletes a copy', function () {
    $avtoreferat = Avtoreferat::factory()->create();
    $copy = AvtoreferatCopy::factory()->create(['avtoreferat_id' => $avtoreferat->id]);

    $this->delete(route('admin.avtoreferats.copies.destroy', [$avtoreferat, $copy]))->assertRedirect();

    $this->assertDatabaseMissing('avtoreferat_copies', ['id' => $copy->id]);
});

it('404s when a copy is addressed through an avtoreferat it does not belong to', function () {
    $owner = Avtoreferat::factory()->create();
    $other = Avtoreferat::factory()->create();
    $copy = AvtoreferatCopy::factory()->create(['avtoreferat_id' => $owner->id]);

    $this->put(route('admin.avtoreferats.copies.update', [$other, $copy]), [
        '_copy_form' => 'edit',
        'inventory_number' => $copy->inventory_number,
    ])->assertNotFound();

    $this->delete(route('admin.avtoreferats.copies.destroy', [$other, $copy]))->assertNotFound();
});

it('shows "Nusxa yo‘q" when an avtoreferat has no copies', function () {
    $avtoreferat = Avtoreferat::factory()->create();

    $this->get(route('admin.avtoreferats.show', $avtoreferat))
        ->assertSee(__('Nusxa yo‘q'));
});

it('lists every copy on the avtoreferat show page', function () {
    $avtoreferat = Avtoreferat::factory()->create();
    AvtoreferatCopy::factory()->create(['avtoreferat_id' => $avtoreferat->id, 'inventory_number' => 'AVT-A']);
    AvtoreferatCopy::factory()->create(['avtoreferat_id' => $avtoreferat->id, 'inventory_number' => 'AVT-B']);

    $this->get(route('admin.avtoreferats.show', $avtoreferat))
        ->assertSee('AVT-A')
        ->assertSee('AVT-B');
});

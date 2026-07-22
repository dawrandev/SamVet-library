<?php

use App\Models\AffiliationGroup;
use App\Models\AffiliationPlace;
use App\Models\AffiliationUnit;
use App\Models\District;
use App\Models\Reader;
use App\Models\Region;

beforeEach(fn () => actingAsAdmin());

it('creates a reader with the affiliation and region/district lookups', function () {
    $place = AffiliationPlace::factory()->create();
    $unit = AffiliationUnit::factory()->create();
    $group = AffiliationGroup::factory()->create();
    $region = Region::factory()->create();
    $district = District::factory()->create(['region_id' => $region->id]);

    $this->post(route('admin.readers.store'), [
        'full_name' => 'Test Foydalanuvchi',
        'type' => 'bachelor',
        'status' => 'active',
        'affiliation_place_id' => $place->id,
        'affiliation_unit_id' => $unit->id,
        'affiliation_group_id' => $group->id,
        'region_id' => $region->id,
        'district_id' => $district->id,
    ])->assertRedirect();

    $reader = Reader::firstWhere('full_name', 'Test Foydalanuvchi');
    expect($reader)->not->toBeNull()
        ->and($reader->affiliation_place_id)->toBe($place->id)
        ->and($reader->affiliation_unit_id)->toBe($unit->id)
        ->and($reader->affiliation_group_id)->toBe($group->id)
        ->and($reader->region_id)->toBe($region->id)
        ->and($reader->district_id)->toBe($district->id);
});

it('creates a reader without any of the optional lookups', function () {
    $this->post(route('admin.readers.store'), [
        'full_name' => 'Lookupsiz foydalanuvchi',
        'type' => 'bachelor',
        'status' => 'active',
    ])->assertRedirect();

    $reader = Reader::firstWhere('full_name', 'Lookupsiz foydalanuvchi');
    expect($reader)->not->toBeNull()
        ->and($reader->affiliation_place_id)->toBeNull()
        ->and($reader->district_id)->toBeNull();
});

it('shows the resolved lookup names on the reader show page', function () {
    $place = AffiliationPlace::factory()->create(['name' => 'SamVet universiteti']);
    $district = District::factory()->create(['name' => 'Chimboy tumani']);
    $reader = Reader::factory()->create([
        'affiliation_place_id' => $place->id,
        'district_id' => $district->id,
    ]);

    $this->get(route('admin.readers.show', $reader))
        ->assertSee('SamVet universiteti')
        ->assertSee('Chimboy tumani');
});

it('updates a reader’s affiliation and district lookups', function () {
    $reader = Reader::factory()->create();
    $newPlace = AffiliationPlace::factory()->create();
    $newDistrict = District::factory()->create();

    $this->put(route('admin.readers.update', $reader), [
        'full_name' => $reader->full_name,
        'type' => $reader->type->value,
        'status' => $reader->status->value,
        'affiliation_place_id' => $newPlace->id,
        'district_id' => $newDistrict->id,
    ])->assertRedirect();

    $reader->refresh();
    expect($reader->affiliation_place_id)->toBe($newPlace->id)
        ->and($reader->district_id)->toBe($newDistrict->id);
});

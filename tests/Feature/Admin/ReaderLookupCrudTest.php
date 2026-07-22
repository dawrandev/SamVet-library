<?php

use App\Models\AffiliationPlace;
use App\Models\District;
use App\Models\Reader;
use App\Models\Region;

beforeEach(fn () => actingAsAdmin());

// --- Simple (non-parented) lookup — representative of AffiliationPlace/Unit/Group/Region ---

it('creates an affiliation place', function () {
    $this->post(route('admin.lookups.affiliation-places.store'), [
        'name' => 'SamVet universiteti',
    ])->assertRedirect(route('admin.lookups.affiliation-places.index'));

    expect(AffiliationPlace::where('name', 'SamVet universiteti')->exists())->toBeTrue();
});

it('deletes an unused affiliation place', function () {
    $place = AffiliationPlace::factory()->create();

    $this->delete(route('admin.lookups.affiliation-places.destroy', $place))->assertRedirect();

    $this->assertDatabaseMissing('affiliation_places', ['id' => $place->id]);
});

it('nulls the reader’s reference when its affiliation place is deleted', function () {
    $place = AffiliationPlace::factory()->create();
    $reader = Reader::factory()->create(['affiliation_place_id' => $place->id]);

    $this->delete(route('admin.lookups.affiliation-places.destroy', $place))->assertRedirect();

    expect($reader->fresh()->affiliation_place_id)->toBeNull();
});

// --- Parented lookup (District -> Region) ---

it('creates a district under a region', function () {
    $region = Region::factory()->create(['name' => 'Qoraqalpog‘iston']);

    $this->post(route('admin.lookups.districts.store'), [
        'name' => 'Chimboy',
        'parent_id' => $region->id,
    ])->assertRedirect(route('admin.lookups.districts.index'));

    $district = District::where('name', 'Chimboy')->first();
    expect($district)->not->toBeNull()
        ->and($district->region_id)->toBe($region->id);
});

it('creates a district with no region', function () {
    $this->post(route('admin.lookups.districts.store'), [
        'name' => 'Noma’lum tuman',
    ])->assertRedirect();

    $district = District::where('name', 'Noma’lum tuman')->first();
    expect($district)->not->toBeNull()
        ->and($district->region_id)->toBeNull();
});

it('rejects a district with a non-existent region', function () {
    $this->from(route('admin.lookups.districts.index'))
        ->post(route('admin.lookups.districts.store'), [
            'name' => 'Xato tuman',
            'parent_id' => 999999,
        ])
        ->assertSessionHasErrors('parent_id');
});

// --- Cascading districts-by-region JSON endpoint ---

it('returns only the districts belonging to the given region', function () {
    $karakalpakstan = Region::factory()->create();
    $samarqand = Region::factory()->create();
    District::factory()->create(['name' => 'Nukus', 'region_id' => $karakalpakstan->id]);
    District::factory()->create(['name' => 'Chimboy', 'region_id' => $karakalpakstan->id]);
    District::factory()->create(['name' => 'Urgut', 'region_id' => $samarqand->id]);

    $response = $this->getJson(route('admin.regions.districts.lookup', $karakalpakstan))->assertOk()->json();

    expect($response['districts'])->toHaveCount(2)
        ->and(collect($response['districts'])->pluck('name')->sort()->values()->all())->toBe(['Chimboy', 'Nukus']);
});

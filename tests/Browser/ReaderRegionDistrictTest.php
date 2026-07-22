<?php

use App\Models\District;
use App\Models\Region;
use App\Models\User;
use Laravel\Dusk\Browser;

it('filters the district select to the chosen region and saves the picked district', function () {
    $admin = User::factory()->create();
    $karakalpakstan = Region::factory()->create(['name' => 'Qoraqalpog‘iston']);
    $samarqand = Region::factory()->create(['name' => 'Samarqand']);
    $nukus = District::factory()->create(['name' => 'Nukus', 'region_id' => $karakalpakstan->id]);
    $urgut = District::factory()->create(['name' => 'Urgut', 'region_id' => $samarqand->id]);

    $this->browse(function (Browser $browser) use ($admin, $karakalpakstan, $nukus, $urgut) {
        $browser->loginAs($admin)
            ->visit('/admin/readers/create')
            ->select('region_id', (string) $karakalpakstan->id)
            // The district list is fetched over AJAX once a region is chosen.
            ->waitFor('#district_id option[value="'.$nukus->id.'"]', 10)
            ->assertSelectHasOptions('district_id', [(string) $nukus->id])
            ->assertSelectMissingOptions('district_id', [(string) $urgut->id])
            ->select('district_id', (string) $nukus->id)
            ->type('full_name', 'Dusk Sinov Foydalanuvchi')
            ->select('type', 'bachelor')
            ->select('status', 'active')
            ->press('Saqlash')
            ->waitForText('Dusk Sinov Foydalanuvchi', 10);

        $reader = \App\Models\Reader::firstWhere('full_name', 'Dusk Sinov Foydalanuvchi');
        expect($reader)->not->toBeNull()
            ->and($reader->region_id)->toBe($karakalpakstan->id)
            ->and($reader->district_id)->toBe($nukus->id);
    });
});

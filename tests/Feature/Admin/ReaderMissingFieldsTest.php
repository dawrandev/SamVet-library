<?php

use App\Models\District;
use App\Models\Reader;

beforeEach(fn () => actingAsAdmin());

it('flags a blank required field in red on the reader show page', function () {
    $reader = Reader::factory()->create(['phone' => null, 'district_id' => District::factory()->create(['name' => 'Chimboy'])->id]);

    $this->get(route('admin.readers.show', $reader))
        ->assertOk()
        ->assertSee('To‘ldirilmagan')
        ->assertSee('text-error-500', false);
});

it('does not flag a filled field', function () {
    $reader = Reader::factory()->create(['phone' => '+998901234567']);

    $this->get(route('admin.readers.show', $reader))
        ->assertOk()
        ->assertSee('+998901234567');
});

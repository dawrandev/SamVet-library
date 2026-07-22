<?php

use App\Models\Reader;

beforeEach(fn () => actingAsAdmin());

it('downloads an Excel export of the reader list', function () {
    Reader::factory()->count(2)->create();

    $response = $this->get(route('admin.readers.export'));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('spreadsheet');
});

it('respects the current filters when exporting readers', function () {
    Reader::factory()->create(['status' => 'left']);

    $this->get(route('admin.readers.export', ['status' => 'left']))->assertOk();
});

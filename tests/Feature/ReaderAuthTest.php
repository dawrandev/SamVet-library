<?php

use App\Models\Reader;

it('shows the reader login page', function () {
    $this->get(route('reader.login'))->assertOk();
});

it('signs a reader in with the right id number and shared password', function () {
    $reader = Reader::factory()->create(['id_number' => 'BT0001']);

    $this->post(route('reader.login'), ['id_number' => 'BT0001', 'password' => 'arm777'])
        ->assertRedirect();

    $this->assertAuthenticatedAs($reader, 'reader');
});

it('accepts the id number case-insensitively', function () {
    Reader::factory()->create(['id_number' => 'BT0001']);

    $this->post(route('reader.login'), ['id_number' => 'bt0001', 'password' => 'arm777']);

    $this->assertAuthenticated('reader');
});

it('rejects a wrong password', function () {
    Reader::factory()->create(['id_number' => 'BT0001']);

    $this->from(route('reader.login'))
        ->post(route('reader.login'), ['id_number' => 'BT0001', 'password' => 'wrong'])
        ->assertSessionHasErrors('id_number');

    $this->assertGuest('reader');
});

it('rejects an unknown id number', function () {
    $this->from(route('reader.login'))
        ->post(route('reader.login'), ['id_number' => 'NOBODY', 'password' => 'arm777'])
        ->assertSessionHasErrors('id_number');

    $this->assertGuest('reader');
});

it('rejects a blocked reader even with the right password', function () {
    Reader::factory()->blocked()->create(['id_number' => 'BT0001']);

    $this->from(route('reader.login'))
        ->post(route('reader.login'), ['id_number' => 'BT0001', 'password' => 'arm777'])
        ->assertSessionHasErrors('id_number');

    $this->assertGuest('reader');
});

it('throttles after five failed attempts', function () {
    Reader::factory()->create(['id_number' => 'BT0001']);

    foreach (range(1, 5) as $i) {
        $this->post(route('reader.login'), ['id_number' => 'BT0001', 'password' => 'wrong']);
    }

    // Even the correct password is now blocked by the rate limiter.
    $this->from(route('reader.login'))
        ->post(route('reader.login'), ['id_number' => 'BT0001', 'password' => 'arm777'])
        ->assertSessionHasErrors('id_number');

    $this->assertGuest('reader');
});

it('logs the reader out', function () {
    $reader = Reader::factory()->create();

    $this->actingAs($reader, 'reader')
        ->post(route('reader.logout'))
        ->assertRedirect();

    $this->assertGuest('reader');
});

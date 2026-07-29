<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('shows the admin login page', function () {
    $this->get(route('login'))->assertOk();
});

it('signs an admin in with the right username and password', function () {
    $user = User::factory()->create(['username' => 'admin', 'password' => Hash::make('secret123')]);

    $this->post(route('login'), ['username' => 'admin', 'password' => 'secret123'])
        ->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticatedAs($user);
});

it('rejects a wrong password', function () {
    User::factory()->create(['username' => 'admin', 'password' => Hash::make('secret123')]);

    $this->from(route('login'))
        ->post(route('login'), ['username' => 'admin', 'password' => 'wrong'])
        ->assertSessionHasErrors('username');

    $this->assertGuest();
});

it('throttles after five failed attempts, even with the correct password', function () {
    User::factory()->create(['username' => 'admin', 'password' => Hash::make('secret123')]);

    foreach (range(1, 5) as $i) {
        $this->post(route('login'), ['username' => 'admin', 'password' => 'wrong']);
    }

    $this->from(route('login'))
        ->post(route('login'), ['username' => 'admin', 'password' => 'secret123'])
        ->assertSessionHasErrors('username');

    $this->assertGuest();
});

it('throttles per username, not globally — a different username is unaffected', function () {
    User::factory()->create(['username' => 'admin', 'password' => Hash::make('secret123')]);
    $other = User::factory()->create(['username' => 'other', 'password' => Hash::make('secret456')]);

    foreach (range(1, 5) as $i) {
        $this->post(route('login'), ['username' => 'admin', 'password' => 'wrong']);
    }

    $this->post(route('login'), ['username' => 'other', 'password' => 'secret456'])
        ->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticatedAs($other);
});

it('logs the admin out', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('admin.logout'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('updates the signed-in admin\'s password when the current password is correct', function () {
    $user = User::factory()->create(['password' => Hash::make('old-password-123')]);
    $this->actingAs($user);

    $this->put(route('admin.password.update'), [
        'current_password' => 'old-password-123',
        'password' => 'a-strong-new-password',
        'password_confirmation' => 'a-strong-new-password',
    ])->assertRedirect(route('admin.password.edit'));

    expect(Hash::check('a-strong-new-password', $user->fresh()->password))->toBeTrue();
});

it('rejects the update when the current password is wrong', function () {
    $user = User::factory()->create(['password' => Hash::make('old-password-123')]);
    $this->actingAs($user);

    $this->from(route('admin.password.edit'))->put(route('admin.password.update'), [
        'current_password' => 'wrong-password',
        'password' => 'a-strong-new-password',
        'password_confirmation' => 'a-strong-new-password',
    ])->assertSessionHasErrors('current_password');

    expect(Hash::check('old-password-123', $user->fresh()->password))->toBeTrue();
});

it('requires the new password to be confirmed and at least 10 characters', function () {
    $user = User::factory()->create(['password' => Hash::make('old-password-123')]);
    $this->actingAs($user);

    $this->from(route('admin.password.edit'))->put(route('admin.password.update'), [
        'current_password' => 'old-password-123',
        'password' => 'short',
        'password_confirmation' => 'short',
    ])->assertSessionHasErrors('password');

    $this->from(route('admin.password.edit'))->put(route('admin.password.update'), [
        'current_password' => 'old-password-123',
        'password' => 'a-strong-new-password',
        'password_confirmation' => 'does-not-match',
    ])->assertSessionHasErrors('password');
});

it('blocks guests from the password-change page and its submit route', function () {
    $this->get(route('admin.password.edit'))->assertRedirect(route('login'));
    $this->put(route('admin.password.update'), [])->assertRedirect(route('login'));
});

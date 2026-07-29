<?php

namespace App\Services\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Self-service account actions for the signed-in admin (e.g. password change).
 * Not a lookup/CRUD resource, so no Repository layer — a single-field update
 * on the already-authenticated user.
 */
class AccountService
{
    public function updatePassword(User $user, string $newPassword): void
    {
        $user->update(['password' => Hash::make($newPassword)]);
    }
}

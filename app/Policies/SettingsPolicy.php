<?php

namespace App\Policies;

use App\Models\User;

class SettingsPolicy
{
    public function viewSettings(User $user): bool
    {
        return $user->role->value === 'ADMIN';
    }

    public function updateSettings(User $user): bool
    {
        return $user->role->value === 'ADMIN';
    }

    public function manageUsers(User $user): bool
    {
        return $user->role->value === 'ADMIN';
    }
}

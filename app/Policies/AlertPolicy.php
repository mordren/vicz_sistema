<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Alert;
use App\Models\User;

class AlertPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // all roles can see alerts
    }

    public function view(User $user, Alert $alert): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    public function update(User $user, Alert $alert): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    public function delete(User $user, Alert $alert): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    /**
     * Any authenticated user may mark an alert as read or resolve it.
     */
    public function markRead(User $user, Alert $alert): bool
    {
        return true;
    }

    public function resolve(User $user, Alert $alert): bool
    {
        return true;
    }
}

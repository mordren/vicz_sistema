<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\AccountReceivable;
use App\Models\User;

class AccountReceivablePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::ADMIN, UserRole::FINANCE]);
    }

    public function view(User $user, AccountReceivable $receivable): bool
    {
        return in_array($user->role, [UserRole::ADMIN, UserRole::FINANCE]);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::ADMIN, UserRole::FINANCE]);
    }

    public function update(User $user, AccountReceivable $receivable): bool
    {
        return in_array($user->role, [UserRole::ADMIN, UserRole::FINANCE]);
    }

    public function delete(User $user, AccountReceivable $receivable): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    public function markAsReceived(User $user, AccountReceivable $receivable): bool
    {
        return in_array($user->role, [UserRole::ADMIN, UserRole::FINANCE]);
    }
}

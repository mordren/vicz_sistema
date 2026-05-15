<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\AccountPayable;
use App\Models\User;

class AccountPayablePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::ADMIN, UserRole::FINANCE]);
    }

    public function view(User $user, AccountPayable $payable): bool
    {
        return in_array($user->role, [UserRole::ADMIN, UserRole::FINANCE]);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::ADMIN, UserRole::FINANCE]);
    }

    public function update(User $user, AccountPayable $payable): bool
    {
        return in_array($user->role, [UserRole::ADMIN, UserRole::FINANCE]);
    }

    public function delete(User $user, AccountPayable $payable): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    public function markAsPaid(User $user, AccountPayable $payable): bool
    {
        return in_array($user->role, [UserRole::ADMIN, UserRole::FINANCE]);
    }
}

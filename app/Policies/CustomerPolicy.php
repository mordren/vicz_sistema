<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::ADMIN, UserRole::FINANCE, UserRole::OPERATIONS, UserRole::SALES], true);
    }

    public function view(User $user, Customer $customer): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::ADMIN, UserRole::SALES], true);
    }

    public function update(User $user, Customer $customer): bool
    {
        return in_array($user->role, [UserRole::ADMIN, UserRole::SALES], true);
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->role === UserRole::ADMIN;
    }
}

<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Proposal;
use App\Models\User;

class ProposalPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::ADMIN, UserRole::FINANCE, UserRole::SALES]);
    }

    public function view(User $user, Proposal $proposal): bool
    {
        return in_array($user->role, [UserRole::ADMIN, UserRole::FINANCE, UserRole::SALES]);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::ADMIN, UserRole::SALES]);
    }

    public function update(User $user, Proposal $proposal): bool
    {
        if ($user->role === UserRole::ADMIN) {
            return true;
        }

        return $user->role === UserRole::SALES
            && $proposal->sales_owner_id === $user->id;
    }

    public function delete(User $user, Proposal $proposal): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    public function approve(User $user, Proposal $proposal): bool
    {
        return in_array($user->role, [UserRole::ADMIN, UserRole::SALES]);
    }

    public function markAsLost(User $user, Proposal $proposal): bool
    {
        if ($user->role === UserRole::ADMIN) {
            return true;
        }

        return $user->role === UserRole::SALES
            && $proposal->sales_owner_id === $user->id;
    }

    public function cancel(User $user, Proposal $proposal): bool
    {
        return $this->markAsLost($user, $proposal);
    }

    public function convertToWorkOrder(User $user, Proposal $proposal): bool
    {
        return in_array($user->role, [UserRole::ADMIN, UserRole::SALES], true);
    }
}

<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\WorkOrder;

class WorkOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // all authenticated roles can list work orders
    }

    public function view(User $user, WorkOrder $workOrder): bool
    {
        return true; // all authenticated roles can view a work order
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::ADMIN, UserRole::OPERATIONS]);
    }

    public function update(User $user, WorkOrder $workOrder): bool
    {
        if ($user->role === UserRole::ADMIN) {
            return true;
        }

        return $user->role === UserRole::OPERATIONS
            && $workOrder->responsible_user_id === $user->id;
    }

    public function delete(User $user, WorkOrder $workOrder): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    /**
     * Operational status updates (progress %, status transitions).
     * OPERATIONS can update their own assigned work orders; ADMIN can update any.
     */
    public function updateStatus(User $user, WorkOrder $workOrder): bool
    {
        if ($user->role === UserRole::ADMIN) {
            return true;
        }

        return $user->role === UserRole::OPERATIONS
            && $workOrder->responsible_user_id === $user->id;
    }
}

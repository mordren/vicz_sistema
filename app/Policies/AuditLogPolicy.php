<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;

class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::ADMIN, UserRole::FINANCE]);
    }

    public function view(User $user, AuditLog $auditLog): bool
    {
        return in_array($user->role, [UserRole::ADMIN, UserRole::FINANCE]);
    }

    /**
     * Audit logs are immutable — no create, update, or delete via policy.
     * They are written only through the AuditService.
     */
    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, AuditLog $auditLog): bool
    {
        return false;
    }

    public function delete(User $user, AuditLog $auditLog): bool
    {
        return false;
    }
}

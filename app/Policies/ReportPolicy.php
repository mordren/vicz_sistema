<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

/**
 * ReportPolicy governs access to generated reports (cash-flow, margin, ageing, etc.).
 * Reports are read-only; no create/update/delete needed.
 */
class ReportPolicy
{
    /**
     * ADMIN and FINANCE can access all reports.
     * OPERATIONS can access operational reports (work orders, production).
     * SALES can access sales / proposal reports.
     */
    public function viewAny(User $user): bool
    {
        return true; // all roles may access at least one report section
    }

    public function viewFinancial(User $user): bool
    {
        return in_array($user->role, [UserRole::ADMIN, UserRole::FINANCE]);
    }

    public function viewOperational(User $user): bool
    {
        return in_array($user->role, [UserRole::ADMIN, UserRole::OPERATIONS]);
    }

    public function viewSales(User $user): bool
    {
        return in_array($user->role, [UserRole::ADMIN, UserRole::SALES]);
    }

    public function viewCashFlow(User $user): bool
    {
        return in_array($user->role, [UserRole::ADMIN, UserRole::FINANCE]);
    }
}

<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Attachment;
use App\Models\User;

class AttachmentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Attachment $attachment): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        // OPERATIONS can upload to work orders; FINANCE can upload to payables/receivables; ADMIN always
        return in_array($user->role, [UserRole::ADMIN, UserRole::FINANCE, UserRole::OPERATIONS, UserRole::SALES]);
    }

    public function delete(User $user, Attachment $attachment): bool
    {
        if ($user->role === UserRole::ADMIN) {
            return true;
        }

        return $attachment->uploaded_by === $user->id;
    }
}

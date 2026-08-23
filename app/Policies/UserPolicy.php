<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Security\CrmPermission;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(CrmPermission::USERS_VIEW);
    }

    public function view(User $user, User $managedUser): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(CrmPermission::USERS_CREATE);
    }

    public function update(User $user, User $managedUser): bool
    {
        return $user->hasPermission(CrmPermission::USERS_UPDATE);
    }

    public function changeStatus(User $user, User $managedUser): bool
    {
        return $user->hasPermission(CrmPermission::USERS_ACTIVATE);
    }

    public function resetPassword(User $user, User $managedUser): bool
    {
        return $user->hasPermission(
            CrmPermission::USERS_RESET_PASSWORD,
        );
    }
}

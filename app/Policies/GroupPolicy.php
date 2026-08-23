<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Group;
use App\Models\User;
use App\Security\CrmPermission;

class GroupPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(CrmPermission::GROUPS_VIEW);
    }

    public function view(User $user, Group $group): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(CrmPermission::GROUPS_CREATE);
    }

    public function update(User $user, Group $group): bool
    {
        return $user->hasPermission(CrmPermission::GROUPS_UPDATE);
    }

    public function delete(User $user, Group $group): bool
    {
        return ! $group->is_system
            && $user->hasPermission(CrmPermission::GROUPS_DELETE);
    }

    public function assignPermissions(User $user): bool
    {
        return $user->hasPermission(
            CrmPermission::GROUPS_ASSIGN_PERMISSIONS,
        );
    }
}

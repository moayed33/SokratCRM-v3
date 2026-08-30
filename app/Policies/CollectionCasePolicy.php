<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CollectionCase;
use App\Models\User;
use App\Security\CrmPermission;

class CollectionCasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(CrmPermission::COLLECTIONS_VIEW);
    }

    public function view(User $user, CollectionCase $collectionCase): bool
    {
        if (! $user->hasPermission(CrmPermission::COLLECTIONS_VIEW)) {
            return false;
        }

        return CollectionCase::query()
            ->whereKey($collectionCase->getKey())
            ->accessibleTo($user)
            ->exists();
    }

    public function assign(User $user, CollectionCase $collectionCase): bool
    {
        return $user->hasPermission(CrmPermission::COLLECTIONS_ASSIGN)
            && $this->isInManagedBranch($user, $collectionCase);
    }

    public function manage(User $user, CollectionCase $collectionCase): bool
    {
        return $user->hasPermission(CrmPermission::COLLECTIONS_MANAGE)
            && $this->isInManagedBranch($user, $collectionCase);
    }

    public function collect(User $user, CollectionCase $collectionCase): bool
    {
        return $user->isSuperAdmin()
            || ($user->hasPermission(CrmPermission::COLLECTIONS_COLLECT)
                && (int) $collectionCase->assigned_collector_user_id === (int) $user->getKey());
    }

    public function complete(User $user, CollectionCase $collectionCase): bool
    {
        return $user->isSuperAdmin()
            || ($user->hasPermission(CrmPermission::COLLECTIONS_COMPLETE)
                && (int) $collectionCase->assigned_collector_user_id === (int) $user->getKey());
    }

    public function cancel(User $user, CollectionCase $collectionCase): bool
    {
        return $user->hasPermission(CrmPermission::COLLECTIONS_CANCEL)
            && $this->isInManagedBranch($user, $collectionCase);
    }

    private function isInManagedBranch(User $user, CollectionCase $collectionCase): bool
    {
        return $user->isSuperAdmin()
            || ($user->branch_id !== null
                && $collectionCase->branch_id !== null
                && (int) $user->branch_id === (int) $collectionCase->branch_id);
    }
}

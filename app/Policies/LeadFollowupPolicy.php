<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LeadFollowup;
use App\Models\User;
use App\Security\CrmPermission;

class LeadFollowupPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(
            CrmPermission::LEADS_FOLLOWUPS_VIEW,
        );
    }

    public function view(User $user, LeadFollowup $followup): bool
    {
        return $this->viewAny($user)
            && $followup->lead->isAccessibleTo($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(
            CrmPermission::LEADS_FOLLOWUPS_CREATE,
        );
    }
}

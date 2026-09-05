<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;
use App\Security\CrmPermission;

class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(CrmPermission::LEADS_VIEW);
    }

    public function view(User $user, Lead $lead): bool
    {
        if (! $user->hasPermission(CrmPermission::LEADS_VIEW)) {
            return false;
        }

        if ($lead->isAccessibleTo($user)) {
            return true;
        }

        // Cross-branch read-only viewing: if lead belongs to a different branch
        if ($user->branch_id !== null && $lead->branch_id !== null && (int) $lead->branch_id !== (int) $user->branch_id) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(CrmPermission::LEADS_CREATE);
    }

    public function update(User $user, Lead $lead): bool
    {
        return $user->hasPermission(CrmPermission::LEADS_UPDATE)
            && $lead->isAccessibleTo($user);
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $user->hasPermission(CrmPermission::LEADS_DELETE)
            && $lead->isAccessibleTo($user);
    }

    public function import(User $user): bool
    {
        return $user->hasPermission(CrmPermission::LEADS_IMPORT);
    }

    public function export(User $user): bool
    {
        return $user->hasPermission(CrmPermission::LEADS_EXPORT);
    }

    public function viewFollowups(User $user, Lead $lead): bool
    {
        return $user->hasPermission(CrmPermission::LEADS_FOLLOWUPS_VIEW)
            && $lead->isAccessibleTo($user);
    }

    public function createFollowup(User $user, Lead $lead): bool
    {
        return $user->hasPermission(CrmPermission::LEADS_FOLLOWUPS_CREATE)
            && $lead->isAccessibleTo($user);
    }
}

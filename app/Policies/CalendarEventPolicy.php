<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CalendarEvent;
use App\Models\User;
use App\Security\CrmPermission;

class CalendarEventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(CrmPermission::CALENDAR_VIEW);
    }

    public function view(User $user, CalendarEvent $event): bool
    {
        return $user->hasPermission(CrmPermission::CALENDAR_VIEW)
            && $event->isAccessibleTo($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(CrmPermission::CALENDAR_MANAGE);
    }

    public function update(User $user, CalendarEvent $event): bool
    {
        return $user->hasPermission(CrmPermission::CALENDAR_MANAGE)
            && $event->isAccessibleTo($user);
    }

    public function delete(User $user, CalendarEvent $event): bool
    {
        return $user->hasPermission(CrmPermission::CALENDAR_MANAGE)
            && $event->isAccessibleTo($user);
    }
}

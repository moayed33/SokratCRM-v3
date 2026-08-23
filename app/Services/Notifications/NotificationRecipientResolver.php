<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Models\CalendarEvent;
use App\Models\Group;
use App\Models\Lead;
use App\Models\NotificationRule;
use App\Models\User;
use App\Security\CrmPermission;
use Illuminate\Support\Collection;

class NotificationRecipientResolver
{
    /** @return Collection<int, User> */
    public function resolve(NotificationRule $rule, Lead|CalendarEvent $source): Collection
    {
        $rule->loadMissing('recipients');
        $users = collect();

        foreach ($rule->recipients as $recipient) {
            $resolved = match ($recipient->recipient_type) {
                'assigned_user' => $this->assignedUser($source),
                'event_owner' => $this->eventOwner($source),
                'explicit_user' => User::query()->find($recipient->recipient_id),
                'group' => Group::query()->find($recipient->recipient_id)?->users()
                    ->where('is_active', true)->get(),
                default => null,
            };

            if ($resolved instanceof User) {
                $users->push($resolved);
            } elseif ($resolved instanceof Collection) {
                $users = $users->merge($resolved);
            }
        }

        return $users
            ->filter(fn (mixed $user): bool => $user instanceof User
                && $user->is_active
                && $this->canReceive($user, $source))
            ->unique(fn (User $user): int => (int) $user->getKey())
            ->values();
    }

    private function assignedUser(Lead|CalendarEvent $source): ?User
    {
        return $source instanceof Lead
            ? $source->assignedUser
            : $source->user;
    }

    private function eventOwner(Lead|CalendarEvent $source): ?User
    {
        return $source instanceof Lead
            ? $source->creator
            : $source->user;
    }

    public function canReceive(User $user, Lead|CalendarEvent|User $source): bool
    {
        if ($source instanceof User) {
            return $user->is($source);
        }

        if ($source instanceof Lead) {
            return $user->hasPermission(CrmPermission::TASKS_VIEW)
                && $source->isAccessibleTo($user);
        }

        return $user->hasPermission(CrmPermission::CALENDAR_VIEW)
            && $source->isAccessibleTo($user);
    }
}

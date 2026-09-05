<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Models\CalendarEvent;
use App\Models\CollectionCase;
use App\Models\Group;
use App\Models\Lead;
use App\Models\NotificationRule;
use App\Models\User;
use App\Security\CrmPermission;
use Illuminate\Support\Collection;

class NotificationRecipientResolver
{
    /** @return Collection<int, User> */
    public function resolve(NotificationRule $rule, Lead|CalendarEvent|CollectionCase $source): Collection
    {
        $rule->loadMissing('recipients');
        $users = collect();

        if ($source instanceof CollectionCase) {
            if ($rule->event_key === NotificationRule::EVENT_COLLECTION_CALL_CENTER_ESCALATED) {
                if ($source->lead?->assignedUser) {
                    $users->push($source->lead->assignedUser);
                }
                if ($source->assignedCollector?->manager) {
                    $users->push($source->assignedCollector->manager);
                }
            } elseif ($rule->event_key === NotificationRule::EVENT_COLLECTION_CALL_CENTER_RESOLVED) {
                if ($source->assignedCollector) {
                    $users->push($source->assignedCollector);
                }
                if ($source->assignedCollector?->manager) {
                    $users->push($source->assignedCollector->manager);
                }
            }
        }

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

    private function assignedUser(Lead|CalendarEvent|CollectionCase $source): ?User
    {
        return match (true) {
            $source instanceof Lead => $source->assignedUser,
            $source instanceof CollectionCase => $source->assignedCollector,
            default => $source->user,
        };
    }

    private function eventOwner(Lead|CalendarEvent|CollectionCase $source): ?User
    {
        return match (true) {
            $source instanceof Lead => $source->creator,
            $source instanceof CollectionCase => $source->createdBy,
            default => $source->user,
        };
    }

    public function canReceive(User $user, Lead|CalendarEvent|CollectionCase|User $source): bool
    {
        if ($source instanceof User) {
            return $user->is($source);
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($source instanceof Lead) {
            return ($user->hasPermission(CrmPermission::TASKS_VIEW)
                || $user->hasPermission(CrmPermission::LEADS_VIEW)
                || $user->hasPermission(CrmPermission::LEADS_FOLLOWUPS_VIEW))
                && $source->isAccessibleTo($user);
        }

        if ($source instanceof CollectionCase) {
            // Allow call center agent, assigned collector, and direct manager to receive escalation alerts
            if (
                (int) $user->id === (int) ($source->lead?->assigned_user_id ?? 0)
                || (int) $user->id === (int) ($source->assigned_collector_user_id ?? 0)
                || (int) $user->id === (int) ($source->assignedCollector?->manager_id ?? 0)
            ) {
                return true;
            }

            return ($user->hasPermission(CrmPermission::COLLECTIONS_VIEW)
                || $user->hasPermission(CrmPermission::COLLECTIONS_COLLECT)
                || $user->hasPermission(CrmPermission::COLLECTIONS_MANAGE)
                || $user->hasPermission(CrmPermission::COLLECTIONS_ASSIGN)
                || $user->hasPermission(CrmPermission::COLLECTIONS_ESCALATIONS_RESPOND))
                && CollectionCase::query()->whereKey($source->getKey())->accessibleTo($user)->exists();
        }

        return ($user->hasPermission(CrmPermission::CALENDAR_VIEW)
            || ($source->type === 'task' && $user->hasPermission(CrmPermission::TASKS_VIEW)))
            && $source->isAccessibleTo($user);
    }
}

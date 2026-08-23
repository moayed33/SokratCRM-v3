<?php

declare(strict_types=1);

namespace App\Security;

use App\Models\Group;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

final class LeadAssignment
{
    public const GROUP_PERMISSION_PREFIX = 'leads.assign.group.';

    public static function groupPermissionCode(Group|int $group): string
    {
        $groupId = $group instanceof Group
            ? (int) $group->getKey()
            : $group;

        return self::GROUP_PERMISSION_PREFIX.$groupId;
    }

    public static function synchronizeGroupPermission(Group $group): Permission
    {
        return Permission::query()->updateOrCreate(
            ['code' => self::groupPermissionCode($group)],
            [
                'module' => 'leads',
                'name_ar' => 'إسناد عميل إلى مجموعة: '.$group->name,
                'description' => 'يسمح باختيار مستخدم نشط من هذه المجموعة عند إضافة عميل أو إعادة إسناده.',
            ],
        );
    }

    public static function deleteGroupPermission(Group $group): void
    {
        Permission::query()
            ->where('code', self::groupPermissionCode($group))
            ->delete();
    }

    public static function synchronizeGroupPermissions(): void
    {
        Group::query()->each(
            static fn (Group $group): Permission => self::synchronizeGroupPermission($group),
        );
    }

    public static function canAssignTo(User $actor, User $target): bool
    {
        if (! $target->is_active) {
            return false;
        }

        if ($actor->is($target)) {
            return true;
        }

        if (! $actor->hasPermission(CrmPermission::LEADS_ASSIGN)) {
            return false;
        }

        if ($actor->isSuperAdmin()) {
            return true;
        }

        $target->loadMissing('groups:id');
        $permittedGroupIds = self::permittedTargetGroupIds($actor);

        return $target->groups->contains(
            static fn (Group $group): bool => in_array(
                (int) $group->getKey(),
                $permittedGroupIds,
                true,
            ),
        );
    }

    /**
     * @return Collection<int, User>
     */
    public static function assignableUsers(User $actor): Collection
    {
        $query = User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->orderBy('username');

        if ($actor->isSuperAdmin()) {
            return $query->get(['id', 'name', 'username', 'is_active']);
        }

        $permittedGroupIds = $actor->hasPermission(CrmPermission::LEADS_ASSIGN)
            ? self::permittedTargetGroupIds($actor)
            : [];

        $query->where(function ($userQuery) use (
            $actor,
            $permittedGroupIds,
        ): void {
            $userQuery->whereKey($actor->getKey());

            if ($permittedGroupIds !== []) {
                $userQuery->orWhereHas(
                    'groups',
                    static fn ($groupQuery) => $groupQuery->whereKey($permittedGroupIds),
                );
            }
        });

        return $query->get(['id', 'name', 'username', 'is_active']);
    }

    /**
     * @return list<int>
     */
    private static function permittedTargetGroupIds(User $actor): array
    {
        $actor->loadMissing('groups.permissions');

        return $actor->groups
            ->flatMap(
                static fn (Group $group) => $group->permissions->pluck('code'),
            )
            ->filter(
                static fn (mixed $code): bool => is_string($code)
                    && str_starts_with($code, self::GROUP_PERMISSION_PREFIX),
            )
            ->map(
                static fn (string $code): int => (int) substr(
                    $code,
                    strlen(self::GROUP_PERMISSION_PREFIX),
                ),
            )
            ->filter(static fn (int $groupId): bool => $groupId > 0)
            ->unique()
            ->values()
            ->all();
    }
}

<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Branch;
use App\Models\User;

class BranchContext
{
    public const SESSION_KEY = 'selected_branch_id';

    public static function getCurrentBranchId(?User $user = null): ?int
    {
        $user ??= auth()->user();

        if ($user === null) {
            return null;
        }

        if ($user->isSuperAdmin()) {
            $selected = session(self::SESSION_KEY);
            if ($selected === null || $selected === '' || $selected === 'all') {
                return null;
            }

            return (int) $selected;
        }

        return $user->branch_id ? (int) $user->branch_id : null;
    }

    public static function getCurrentBranch(?User $user = null): ?Branch
    {
        $branchId = self::getCurrentBranchId($user);

        if ($branchId === null) {
            return null;
        }

        return Branch::find($branchId);
    }

    public static function isFiltered(?User $user = null): bool
    {
        return self::getCurrentBranchId($user) !== null;
    }

    public static function getActiveBranchLabel(?User $user = null): string
    {
        $user ??= auth()->user();

        if ($user === null) {
            return __('crm.all_branches');
        }

        if ($user->isSuperAdmin()) {
            $branch = self::getCurrentBranch($user);

            return $branch ? $branch->name : __('crm.all_branches');
        }

        return $user->branch?->name ?? __('crm.main_branch');
    }

    public static function setSelectedBranch(string|int|null $branchId): void
    {
        if ($branchId === null || $branchId === '' || $branchId === 'all') {
            session([self::SESSION_KEY => 'all']);
        } else {
            session([self::SESSION_KEY => (int) $branchId]);
        }
    }
}

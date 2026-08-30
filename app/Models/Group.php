<?php

declare(strict_types=1);

namespace App\Models;

use App\Security\LeadAssignment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Group extends Model
{
    public const SUPER_ADMIN_CODE = 'super-admin';
    public const BRANCH_ADMIN_CODE = 'branch-admin';
    public const MANAGER_CODE = 'manager';
    public const COLLECTOR_CODE = 'collector';
    public const EMPLOYEE_CODE = 'employee';

    public const SYSTEM_CODES = [
        self::SUPER_ADMIN_CODE,
        self::BRANCH_ADMIN_CODE,
        self::MANAGER_CODE,
        self::COLLECTOR_CODE,
        self::EMPLOYEE_CODE,
    ];

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_system',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::created(
            static fn (Group $group): Permission => LeadAssignment::synchronizeGroupPermission($group),
        );

        static::updated(static function (Group $group): void {
            if ($group->wasChanged('name')) {
                LeadAssignment::synchronizeGroupPermission($group);
            }
        });

        static::deleting(static function (Group $group): void {
            LeadAssignment::deleteGroupPermission($group);
        });
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->code === self::SUPER_ADMIN_CODE;
    }

    public function isBranchAdmin(): bool
    {
        return $this->code === self::BRANCH_ADMIN_CODE;
    }

    public function isManager(): bool
    {
        return $this->code === self::MANAGER_CODE;
    }

    public function isCollector(): bool
    {
        return $this->code === self::COLLECTOR_CODE;
    }

    public function isEmployee(): bool
    {
        return $this->code === self::EMPLOYEE_CODE;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

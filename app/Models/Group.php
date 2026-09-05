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
    public const COLLECTION_MANAGER_CODE = 'collection-manager';
    public const COLLECTOR_CODE = 'collector';
    public const EMPLOYEE_CODE = 'employee';

    public const SYSTEM_CODES = [
        self::SUPER_ADMIN_CODE,
        self::BRANCH_ADMIN_CODE,
        self::MANAGER_CODE,
        self::COLLECTION_MANAGER_CODE,
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

    public static function generateUniqueCode(string $name, ?int $excludeGroupId = null): string
    {
        $transliterated = \Illuminate\Support\Str::transliterate($name);
        $base = \Illuminate\Support\Str::slug($transliterated, '-');
        $base = preg_replace('/[^a-z0-9-]/i', '', (string) $base);
        $base = trim((string) $base, '-');
        $base = mb_substr($base, 0, 70);

        if (empty($base) || mb_strlen($base) < 2) {
            $base = 'group-'.substr(md5(uniqid('', true)), 0, 6);
        }

        $candidate = $base;
        $counter = 1;
        while (
            in_array($candidate, [self::SUPER_ADMIN_CODE], true)
            || self::query()->where('code', $candidate)->when($excludeGroupId, static fn ($q) => $q->where('id', '!=', $excludeGroupId))->exists()
        ) {
            $counter++;
            $candidate = "{$base}-{$counter}";
        }

        return $candidate;
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

    public function isCollectionManager(): bool
    {
        return $this->code === self::COLLECTION_MANAGER_CODE;
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

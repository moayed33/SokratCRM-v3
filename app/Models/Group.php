<?php

declare(strict_types=1);

namespace App\Models;

use App\Security\LeadAssignment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Group extends Model
{
    public const SUPER_ADMIN_CODE = 'super-admin';

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
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
}

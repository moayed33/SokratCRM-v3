<?php

namespace App\Models;

use App\Security\CrmPermission;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'branch_id',
    'manager_id',
    'name',
    'username',
    'email',
    'voip_extension',
    'locale',
    'timezone',
    'mobile_phone',
    'collection_zone',
    'whatsapp_opt_in_at',
    'password',
    'is_active',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'whatsapp_opt_in_at' => 'datetime',
        ];
    }


    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'manager_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(self::class, 'manager_id');
    }
    public function scopeForBranch(Builder $query, int|string|null $branchId = null): Builder
    {
        if ($branchId !== null && $branchId !== '' && $branchId !== 'all') {
            return $query->where('users.branch_id', (int) $branchId);
        }

        return $query;
    }
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class);
    }

    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(Campaign::class)->withTimestamps();
    }

    public function assignedLeads(): HasMany
    {
        return $this->hasMany(
            Lead::class,
            'assigned_user_id',
        );
    }

    public function createdLeads(): HasMany
    {
        return $this->hasMany(
            Lead::class,
            'created_by_user_id',
        );
    }

    public function recordedDonations(): HasMany
    {
        return $this->hasMany(Donation::class, 'recorded_by_user_id');
    }

    public function assignedCollectionCases(): HasMany
    {
        return $this->hasMany(CollectionCase::class, 'assigned_collector_user_id');
    }

    public function createdCollectionCases(): HasMany
    {
        return $this->hasMany(CollectionCase::class, 'created_by_user_id');
    }

    public function completedCollectionCases(): HasMany
    {
        return $this->hasMany(CollectionCase::class, 'completed_by_user_id');
    }

    public function notificationPreference(): HasOne
    {
        return $this->hasOne(NotificationPreference::class);
    }

    public function notificationOccurrences(): HasMany
    {
        return $this->hasMany(NotificationOccurrence::class, 'recipient_user_id');
    }

    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class);
    }

    public function voipExtensionAssignments(): HasMany
    {
        return $this->hasMany(VoipExtensionAssignment::class);
    }

    public function isSuperAdmin(): bool
    {
        $this->loadMissing('groups');

        return $this->groups->contains(
            'code',
            Group::SUPER_ADMIN_CODE,
        );
    }

    public function isBranchAdmin(): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $this->loadMissing('groups');

        return $this->groups->contains('code', Group::BRANCH_ADMIN_CODE);
    }

    public function isManager(): bool
    {
        if ($this->isSuperAdmin() || $this->isBranchAdmin()) {
            return true;
        }

        $this->loadMissing('groups');

        return $this->groups->contains('code', Group::MANAGER_CODE);
    }

    public function isCollector(): bool
    {
        $this->loadMissing('groups');

        return $this->groups->contains('code', Group::COLLECTOR_CODE);
    }

    public function isEmployee(): bool
    {
        $this->loadMissing('groups');

        return $this->groups->contains('code', Group::EMPLOYEE_CODE);
    }
    public function isTeamLeader(): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $this->loadMissing('groups');
        $hasTeamLeaderGroup = $this->groups->contains(static function (Group $group): bool {
            $code = strtolower((string) $group->code);

            return in_array($code, ['team-leader', 'team_leader', 'sales-manager', 'sales_manager', 'leader', 'manager'], true);
        });

        if ($hasTeamLeaderGroup) {
            return true;
        }

        return $this->hasPermission(CrmPermission::LEADS_SCOPE_GROUP)
            || $this->hasPermission(CrmPermission::LEADS_ASSIGN)
            || $this->hasPermission(CrmPermission::CAMPAIGNS_REPORTS);
    }

    public function isCollectionManager(): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $this->loadMissing('groups');
        $hasCollectionGroup = $this->groups->contains(static function (Group $group): bool {
            $code = strtolower((string) $group->code);

            return in_array($code, ['collection-manager', 'collection_manager', 'collections-manager', 'collections_manager'], true);
        });

        if ($hasCollectionGroup) {
            return true;
        }

        return $this->hasPermission(CrmPermission::COLLECTIONS_MANAGE)
            || $this->hasPermission(CrmPermission::COLLECTIONS_ASSIGN)
            || $this->hasPermission(CrmPermission::COLLECTIONS_REPORTS);
    }

    public function canViewEmployeeStats(): bool
    {
        return $this->isSuperAdmin()
            || $this->hasPermission(CrmPermission::REPORTS_EMPLOYEES_VIEW);
    }

    /**
     * Get accessible employee / staff query based on role scope:
     * - Superadmins: see all staff across all branches and teams.
     * - Team leaders: see team members in their assigned groups / teams.
     * - Collection managers: see collectors they manage.
     */
    public function managedEmployeesQuery(): Builder
    {
        $query = self::query()->where('users.is_active', true);

        if ($this->isSuperAdmin()) {
            return $query;
        }

        $isLeader = $this->isTeamLeader();
        $isCollectionMgr = $this->isCollectionManager();
        $userId = (int) $this->getKey();

        return $query->where(function (Builder $scopeQuery) use ($isLeader, $isCollectionMgr, $userId): void {
            $scopeQuery->where('users.manager_id', $userId)
                ->orWhere('users.id', $userId);

            if ($isLeader) {
                $groupIds = $this->groups->pluck('id')->all();
                if ($groupIds !== []) {
                    $scopeQuery->orWhereHas('groups', static fn ($gq) => $gq->whereIn('groups.id', $groupIds));
                }
            }

            if ($isCollectionMgr) {
                $scopeQuery->orWhere(static function (Builder $cq) use ($userId): void {
                    $cq->whereHas('groups.permissions', static fn ($pq) => $pq->where('code', CrmPermission::COLLECTIONS_COLLECT->value))
                        ->orWhereHas('assignedCollectionCases', static fn ($caseQ) => $caseQ->where('created_by_user_id', $userId)->orWhere('assigned_by_user_id', $userId));
                });
            }
        });
    }

    public function hasPermission(
        CrmPermission|string $permission,
    ): bool {
        if (! $this->is_active) {
            return false;
        }
        if ($this->isSuperAdmin()) {
            return true;
        }

        $code = $permission instanceof CrmPermission
            ? $permission->value
            : $permission;

        $this->loadMissing('groups.permissions');

        return $this->groups->contains(
            static fn (Group $group): bool => $group->permissions
                ->contains('code', $code),
        );
    }
}

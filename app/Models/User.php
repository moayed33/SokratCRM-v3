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
    'name',
    'username',
    'email',
    'voip_extension',
    'locale',
    'timezone',
    'mobile_phone',
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

    public function isSuperAdmin(): bool
    {
        $this->loadMissing('groups');

        return $this->groups->contains(
            'code',
            Group::SUPER_ADMIN_CODE,
        );
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

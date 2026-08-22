<?php

namespace App\Models;

use App\Security\CrmPermission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Lead extends Model
{
    protected $fillable = [
        'branch_id',
        'lead_status_id',
        'name',
        'first_name',
        'last_name',
        'company_name',
        'activity',
        'governorate',
        'address',
        'users_count',
        'branches_count',
        'job_title',
        'disinterest_reason',
        'solution_type',
        'lines_count',
        'extensions',
        'departments',
        'quotation_file_path',
        'phone',
        'email',
        'source',
        'quotation_sent',
        'assigned_employee',
        'assigned_user_id',
        'created_by',
        'created_by_user_id',
        'notes',
        'next_follow_up_at',
        'donation_type',
        'donation_type_id',
        'donation_cycle',
        'donation_value',
        'donation_purpose',
        'donation_purpose_id',
        'response_details',
        'contact_date',
        'responding_user_id',
    ];

    protected function casts(): array
    {
        return [
            'quotation_sent' => 'boolean',
            'users_count' => 'integer',
            'branches_count' => 'integer',
            'lines_count' => 'integer',
            'donation_value' => 'decimal:2',
            'contact_date' => 'datetime',
            'next_follow_up_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function scopeForBranch(Builder $query, int|string|null $branchId = null): Builder
    {
        if ($branchId !== null && $branchId !== '' && $branchId !== 'all') {
            return $query->where('leads.branch_id', (int) $branchId);
        }

        return $query;
    }

    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        if (! $user->isSuperAdmin()) {
            if ($user->branch_id !== null) {
                $query->where('leads.branch_id', (int) $user->branch_id);
            }
        } else {
            $selectedBranch = session(\App\Support\BranchContext::SESSION_KEY);
            if ($selectedBranch !== null && $selectedBranch !== '' && $selectedBranch !== 'all') {
                $query->where('leads.branch_id', (int) $selectedBranch);
            }
        }

        if ($user->hasPermission(CrmPermission::LEADS_SCOPE_ALL)) {
            return $query;
        }

        $groupIds = [];

        if ($user->hasPermission(CrmPermission::LEADS_SCOPE_GROUP)) {
            $user->loadMissing('groups');
            $groupIds = $user->groups->modelKeys();
        }

        return $query->where(
            static function (Builder $accessQuery) use (
                $user,
                $groupIds,
            ): void {
                $accessQuery
                    ->where('assigned_user_id', $user->getKey())
                    ->orWhere('created_by_user_id', $user->getKey());

                if ($groupIds !== []) {
                    $accessQuery->orWhereHas(
                        'assignedUser.groups',
                        static fn (Builder $groupQuery): Builder => $groupQuery
                            ->whereKey($groupIds),
                    );
                }
            },
        );
    }

    public function isAccessibleTo(User $user): bool
    {
        return self::query()
            ->whereKey($this->getKey())
            ->accessibleTo($user)
            ->exists();
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(
            LeadStatus::class,
            'lead_status_id'
        );
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'assigned_user_id'
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by_user_id'
        );
    }

    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(Campaign::class)->withTimestamps();
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(
            LeadStatusHistory::class
        )->orderByDesc('changed_at');
    }

    public function followups(): HasMany
    {
        return $this->hasMany(
            LeadFollowup::class
        )->orderByDesc('followed_up_at');
    }

    public function phones(): HasMany
    {
        return $this->hasMany(LeadPhone::class);
    }

    public function primaryPhone(): HasOne
    {
        return $this->hasOne(LeadPhone::class)->where('is_primary', true);
    }

    public function additionalPhones(): HasMany
    {
        return $this->hasMany(LeadPhone::class)->where('is_primary', false);
    }

    public function relatedPeople(): HasMany
    {
        return $this->hasMany(LeadRelatedPerson::class);
    }

    public function donationTypeRel(): BelongsTo
    {
        return $this->belongsTo(DonationType::class, 'donation_type_id');
    }

    public function donationPurposeRel(): BelongsTo
    {
        return $this->belongsTo(DonationPurpose::class, 'donation_purpose_id');
    }

    public function respondingUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responding_user_id');
    }
}

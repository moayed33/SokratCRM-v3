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
        'governorate_id',
        'subregion_id',
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
        'custom_fields',
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
            'custom_fields' => 'array',
            'contact_date' => 'datetime',
            'next_follow_up_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Lead $lead): void {
            if (empty($lead->lead_status_id)) {
                $defaultStatus = LeadStatus::query()->where('code', 'new')->first()
                    ?? LeadStatus::query()->first();
                if ($defaultStatus) {
                    $lead->lead_status_id = $defaultStatus->id;
                }
            }
        });
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
        if ($user->isSuperAdmin()) {
            $selectedBranch = session(\App\Support\BranchContext::SESSION_KEY);
            if ($selectedBranch !== null && $selectedBranch !== '' && $selectedBranch !== 'all') {
                $query->where('leads.branch_id', (int) $selectedBranch);
            }

            return $query;
        }

        return $query->where(function (Builder $accessQuery) use ($user): void {
            // 1. Direct ownership (assigned or created) is always accessible to the user
            $accessQuery->where(function (Builder $personalQuery) use ($user): void {
                $personalQuery->where('leads.assigned_user_id', $user->getKey())
                    ->orWhere('leads.created_by_user_id', $user->getKey());
            });

            // 2. LEADS_SCOPE_ALL allows access to leads across all branches (or scoped to their branch if user has branch_id)
            if ($user->hasPermission(CrmPermission::LEADS_SCOPE_ALL)) {
                $accessQuery->orWhere(function (Builder $allQuery) use ($user): void {
                    if ($user->branch_id !== null) {
                        $allQuery->where('leads.branch_id', (int) $user->branch_id);
                    } else {
                        $allQuery->whereRaw('1 = 1');
                    }
                });
            }

            // 3. Group-scoped visibility (leads assigned to users in the same group or reporting to this manager)
            if ($user->hasPermission(CrmPermission::LEADS_SCOPE_GROUP)) {
                $user->loadMissing('groups');
                $groupIds = $user->groups->modelKeys();

                $accessQuery->orWhere(function (Builder $groupScopeQuery) use ($user, $groupIds): void {
                    if ($user->branch_id !== null) {
                        $groupScopeQuery->where('leads.branch_id', (int) $user->branch_id);
                    }

                    $groupScopeQuery->where(function (Builder $subScope) use ($user, $groupIds): void {
                        $subScope->whereHas(
                            'assignedUser',
                            static fn (Builder $uq): Builder => $uq->where('users.manager_id', $user->getKey()),
                        );

                        if ($groupIds !== []) {
                            $subScope->orWhereHas(
                                'assignedUser.groups',
                                static fn (Builder $groupQuery): Builder => $groupQuery->whereKey($groupIds),
                            );
                        }
                    });
                });
            }

            // 4. Branch-scoped fallback for users with branch_id
            if ($user->branch_id !== null && ! $user->hasPermission(CrmPermission::LEADS_SCOPE_ALL) && ! $user->hasPermission(CrmPermission::LEADS_SCOPE_GROUP)) {
                $accessQuery->orWhere(function (Builder $branchQuery) use ($user): void {
                    $branchQuery->where('leads.branch_id', (int) $user->branch_id)
                        ->where(function (Builder $scopeQuery) use ($user): void {
                            $scopeQuery->where('leads.assigned_user_id', $user->getKey())
                                ->orWhere('leads.created_by_user_id', $user->getKey());
                        });
                });
            }
        });
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

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    public function subregion(): BelongsTo
    {
        return $this->belongsTo(GovernorateSubregion::class);
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

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class)
            ->orderByDesc('donated_at')
            ->orderByDesc('id');
    }

    public function collectionCases(): HasMany
    {
        return $this->hasMany(CollectionCase::class)
            ->orderByDesc('created_at');
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

    public function stageFieldValues(): HasMany
    {
        return $this->hasMany(LeadStageFieldValue::class, 'lead_id')
            ->orderByDesc('id');
    }

    public function getDisplayPhoneAttribute(): string
    {
        return self::formatDisplayPhone((string) ($this->phone ?? ''), auth()->user());
    }

    public function getMaskedPhoneAttribute(): string
    {
        return self::maskPhone((string) ($this->phone ?? ''));
    }

    public static function formatDisplayPhone(string $phone, ?User $user = null): string
    {
        $clean = trim($phone);
        if (strlen($clean) < 7) {
            return $clean;
        }

        if ($user !== null && $user->can(CrmPermission::LEADS_VIEW_FULL_PHONE->value)) {
            return $clean;
        }

        return self::maskPhone($clean);
    }

    public static function maskPhone(string $phone): string
    {
        $clean = trim($phone);
        if (strlen($clean) < 7) {
            return $clean;
        }

        $isPlus = str_starts_with($clean, '+');
        $prefixLen = $isPlus ? 5 : 4;
        $suffixLen = 3;

        if (strlen($clean) <= ($prefixLen + $suffixLen)) {
            return substr($clean, 0, 3) . '****' . substr($clean, -2);
        }

        return substr($clean, 0, $prefixLen) . '****' . substr($clean, -$suffixLen);
    }
}

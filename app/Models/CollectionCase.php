<?php

declare(strict_types=1);

namespace App\Models;

use App\Security\CrmPermission;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CollectionCase extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_ASSIGNED = 'assigned';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_COLLECTED = 'collected';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    public const OPEN_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_ASSIGNED,
        self::STATUS_SCHEDULED,
        self::STATUS_FAILED,
    ];

    protected $attributes = [
        'cycle' => 'one_time',
        'status' => self::STATUS_PENDING,
    ];

    protected $fillable = [
        'lead_id',
        'source_followup_id',
        'branch_id',
        'donation_type_id',
        'donation_type',
        'expected_amount',
        'cycle',
        'status',
        'due_at',
        'collection_address',
        'notes',
        'assigned_collector_user_id',
        'created_by_user_id',
        'assigned_by_user_id',
        'completed_by_user_id',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'expected_amount' => 'decimal:2',
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', self::OPEN_STATUSES);
    }

    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin()) {
            $selectedBranch = session(BranchContext::SESSION_KEY);

            return $selectedBranch !== null && $selectedBranch !== '' && $selectedBranch !== 'all'
                ? $query->where('collection_cases.branch_id', (int) $selectedBranch)
                : $query;
        }

        $canManageBranch = $user->hasPermission(CrmPermission::COLLECTIONS_MANAGE)
            || $user->hasPermission(CrmPermission::COLLECTIONS_ASSIGN)
            || $user->hasPermission(CrmPermission::COLLECTIONS_REPORTS)
            || $user->hasPermission(CrmPermission::COLLECTIONS_CANCEL);

        return $query->where(function (Builder $accessQuery) use ($user, $canManageBranch): void {
            if ($canManageBranch && $user->branch_id !== null) {
                $accessQuery->where('collection_cases.branch_id', (int) $user->branch_id);

                if ($user->hasPermission(CrmPermission::COLLECTIONS_COLLECT)) {
                    $accessQuery->orWhere('assigned_collector_user_id', $user->getKey());
                }

                return;
            }

            $accessQuery->where('assigned_collector_user_id', $user->getKey());
        });
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function sourceFollowup(): BelongsTo
    {
        return $this->belongsTo(LeadFollowup::class, 'source_followup_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function donationType(): BelongsTo
    {
        return $this->belongsTo(DonationType::class);
    }

    public function assignedCollector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_collector_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by_user_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(CollectionActivity::class)->orderByDesc('created_at');
    }

    public function donation(): HasOne
    {
        return $this->hasOne(Donation::class);
    }
}

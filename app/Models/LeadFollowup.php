<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadFollowup extends Model
{
    protected $fillable = [
        'branch_id',
        'lead_id',
        'from_status_id',
        'to_status_id',
        'employee_name',
        'user_id',
        'communication_type',
        'outcome',
        'field_changes',
        'next_follow_up_at',
        'followed_up_at',
    ];

    protected function casts(): array
    {
        return [
            'field_changes' => 'array',
            'next_follow_up_at' => 'datetime',
            'followed_up_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(static function (LeadFollowup $followup): void {
            if (empty($followup->branch_id)) {
                if ($followup->lead && ! empty($followup->lead->branch_id)) {
                    $followup->branch_id = $followup->lead->branch_id;
                } elseif (auth()->check() && ! empty(auth()->user()->branch_id)) {
                    $followup->branch_id = auth()->user()->branch_id;
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
            return $query->where('lead_followups.branch_id', (int) $branchId);
        }

        return $query;
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(
            Lead::class
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fromStatus(): BelongsTo
    {
        return $this->belongsTo(
            LeadStatus::class,
            'from_status_id'
        );
    }

    public function toStatus(): BelongsTo
    {
        return $this->belongsTo(
            LeadStatus::class,
            'to_status_id'
        );
    }
}

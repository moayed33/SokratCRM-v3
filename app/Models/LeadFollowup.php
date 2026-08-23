<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadFollowup extends Model
{
    protected $fillable = [
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

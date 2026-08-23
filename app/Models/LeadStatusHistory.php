<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadStatusHistory extends Model
{
    protected $fillable = [
        'lead_id',
        'from_status_id',
        'to_status_id',
        'changed_by',
        'changed_by_user_id',
        'note',
        'changed_at',
    ];

    protected function casts(): array
    {
        return [
            'changed_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'changed_by_user_id'
        );
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

<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectionEscalation extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_COLLECT_NOW = 'collect_now';

    public const STATUS_RESCHEDULED = 'rescheduled';

    protected $fillable = [
        'collection_case_id',
        'collector_user_id',
        'call_center_user_id',
        'collection_manager_user_id',
        'status',
        'collector_note',
        'response_note',
        'new_due_at',
        'requested_at',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'new_due_at' => 'datetime',
            'requested_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    public function collectionCase(): BelongsTo
    {
        return $this->belongsTo(CollectionCase::class);
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collector_user_id');
    }

    public function callCenterUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'call_center_user_id');
    }

    public function collectionManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collection_manager_user_id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}

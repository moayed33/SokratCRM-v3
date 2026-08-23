<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationOccurrence extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_DISPATCHED = 'dispatched';

    public const STATUS_CANCELED = 'canceled';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'notification_rule_id',
        'event_key',
        'source_kind',
        'source_id',
        'recipient_user_id',
        'source_due_at',
        'trigger_at',
        'priority',
        'status',
        'payload',
        'notification_id',
        'dispatched_at',
        'canceled_at',
        'dismissed_at',
        'snoozed_until',
    ];

    protected function casts(): array
    {
        return [
            'source_due_at' => 'datetime',
            'trigger_at' => 'datetime',
            'payload' => 'array',
            'dispatched_at' => 'datetime',
            'canceled_at' => 'datetime',
            'dismissed_at' => 'datetime',
            'snoozed_until' => 'datetime',
        ];
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING)
            ->whereNull('canceled_at');
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(NotificationRule::class, 'notification_rule_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(NotificationDelivery::class);
    }
}

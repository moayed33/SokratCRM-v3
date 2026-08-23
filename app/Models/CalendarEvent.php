<?php

declare(strict_types=1);

namespace App\Models;

use App\Security\CrmPermission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CalendarEvent extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'calendar_events';

    protected $fillable = [
        'user_id',
        'lead_id',
        'title',
        'description',
        'start_time',
        'end_time',
        'type',
        'status',
        'sync_id',
        'provider',
        'synced_at',
        'reminder_minutes_before',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'synced_at' => 'datetime',
            'reminder_minutes_before' => 'integer',
        ];
    }

    public function isSynced(): bool
    {
        return ! empty($this->sync_id) && $this->synced_at !== null;
    }

    public function scopeSynced(Builder $query, ?string $provider = null): Builder
    {
        $query->whereNotNull('sync_id')->whereNotNull('synced_at');

        if ($provider !== null) {
            $query->where('provider', $provider);
        }

        return $query;
    }

    public function scopeUpcomingReminders(Builder $query, int $withinMinutes = 30): Builder
    {
        $now = now();
        $threshold = (clone $now)->addMinutes($withinMinutes);

        return $query->where('status', 'scheduled')
            ->where('start_time', '>=', $now)
            ->where('start_time', '<=', $threshold);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function scopeBetween(Builder $query, string|\DateTimeInterface $start, string|\DateTimeInterface $end): Builder
    {
        return $query->where(function (Builder $q) use ($start, $end): void {
            $q->whereBetween('start_time', [$start, $end])
                ->orWhereBetween('end_time', [$start, $end])
                ->orWhere(function (Builder $sub) use ($start, $end): void {
                    $sub->where('start_time', '<=', $start)
                        ->where('end_time', '>=', $end);
                });
        });
    }

    public function scopeForUser(Builder $query, User|int $user): Builder
    {
        $userId = $user instanceof User ? $user->getKey() : $user;

        return $query->where('user_id', $userId);
    }

    public function scopeForLead(Builder $query, Lead|int $lead): Builder
    {
        $leadId = $lead instanceof Lead ? $lead->getKey() : $lead;

        return $query->where('lead_id', $leadId);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeWithStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin() || $user->hasPermission(CrmPermission::LEADS_SCOPE_ALL)) {
            return $query;
        }

        $groupIds = [];
        if ($user->hasPermission(CrmPermission::LEADS_SCOPE_GROUP)) {
            $user->loadMissing('groups');
            $groupIds = $user->groups->modelKeys();
        }

        return $query->where(function (Builder $accessQuery) use ($user, $groupIds): void {
            $accessQuery->where('user_id', $user->getKey())
                ->orWhereHas('lead', function (Builder $leadQuery) use ($user, $groupIds): void {
                    $leadQuery->accessibleTo($user);
                });

            if ($groupIds !== []) {
                $accessQuery->orWhereHas('user.groups', function (Builder $groupQuery) use ($groupIds): void {
                    $groupQuery->whereKey($groupIds);
                });
            }
        });
    }

    public function isAccessibleTo(User $user): bool
    {
        return static::query()
            ->whereKey($this->getKey())
            ->accessibleTo($user)
            ->exists();
    }
}

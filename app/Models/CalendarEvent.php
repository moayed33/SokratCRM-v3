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
        'branch_id',
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

    protected static function booted(): void
    {
        static::creating(static function (CalendarEvent $event): void {
            if (empty($event->branch_id)) {
                if ($event->lead && ! empty($event->lead->branch_id)) {
                    $event->branch_id = $event->lead->branch_id;
                } elseif ($event->user && ! empty($event->user->branch_id)) {
                    $event->branch_id = $event->user->branch_id;
                } elseif (auth()->check() && ! empty(auth()->user()->branch_id)) {
                    $event->branch_id = auth()->user()->branch_id;
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
            return $query->where(function (Builder $q) use ($branchId): void {
                $q->where('calendar_events.branch_id', (int) $branchId)
                    ->orWhereHas('lead', function (Builder $leadQuery) use ($branchId): void {
                        $leadQuery->where('leads.branch_id', (int) $branchId);
                    });
            });
        }

        return $query;
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
        if (! $user->isSuperAdmin()) {
            if ($user->branch_id !== null) {
                $query->where(function (Builder $branchQuery) use ($user): void {
                    $branchQuery->where('calendar_events.branch_id', (int) $user->branch_id)
                        ->orWhere(function (Builder $fallbackQuery) use ($user): void {
                            $fallbackQuery->whereNull('calendar_events.branch_id')
                                ->where(function (Builder $ownerOrLeadQuery) use ($user): void {
                                    $ownerOrLeadQuery->where('calendar_events.user_id', $user->getKey())
                                        ->orWhereHas('lead', function (Builder $leadQuery) use ($user): void {
                                            $leadQuery->where('leads.branch_id', (int) $user->branch_id);
                                        });
                                });
                        });
                });
            }
        } else {
            $selectedBranch = session(\App\Support\BranchContext::SESSION_KEY);
            if ($selectedBranch !== null && $selectedBranch !== '' && $selectedBranch !== 'all') {
                $query->where(function (Builder $branchQuery) use ($selectedBranch): void {
                    $branchQuery->where('calendar_events.branch_id', (int) $selectedBranch)
                        ->orWhereHas('lead', function (Builder $leadQuery) use ($selectedBranch): void {
                            $leadQuery->where('leads.branch_id', (int) $selectedBranch);
                        });
                });
            }
        }

        if ($user->isSuperAdmin() || $user->hasPermission(CrmPermission::LEADS_SCOPE_ALL)) {
            return $query;
        }

        $groupIds = [];
        if ($user->hasPermission(CrmPermission::LEADS_SCOPE_GROUP)) {
            $user->loadMissing('groups');
            $groupIds = $user->groups->modelKeys();
        }

        return $query->where(function (Builder $accessQuery) use ($user, $groupIds): void {
            $accessQuery->where('calendar_events.user_id', $user->getKey())
                ->orWhereHas('lead', function (Builder $leadQuery) use ($user): void {
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

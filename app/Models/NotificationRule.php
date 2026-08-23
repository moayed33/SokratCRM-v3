<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationRule extends Model
{
    use SoftDeletes;

    public const EVENT_FOLLOWUP_DUE = 'followup.due';

    public const EVENT_FOLLOWUP_OVERDUE = 'followup.overdue';

    public const EVENT_FOLLOWUP_RESCHEDULED = 'followup.rescheduled';

    public const EVENT_FOLLOWUP_REASSIGNED = 'followup.reassigned';

    public const EVENT_CALENDAR_DUE = 'calendar.due';

    public const EVENT_CALENDAR_UPDATED = 'calendar.updated';

    public const EVENT_CALENDAR_CANCELED = 'calendar.canceled';

    public const EVENT_SYSTEM_TEST = 'system.test';

    public const EVENTS = [
        self::EVENT_FOLLOWUP_DUE,
        self::EVENT_FOLLOWUP_OVERDUE,
        self::EVENT_FOLLOWUP_RESCHEDULED,
        self::EVENT_FOLLOWUP_REASSIGNED,
        self::EVENT_CALENDAR_DUE,
        self::EVENT_CALENDAR_UPDATED,
        self::EVENT_CALENDAR_CANCELED,
        self::EVENT_SYSTEM_TEST,
    ];

    public const CHANNELS = ['database', 'push', 'mail', 'sms', 'whatsapp'];

    public const PRIORITIES = ['normal', 'important', 'urgent'];

    public const RECIPIENT_TYPES = ['assigned_user', 'event_owner', 'explicit_user', 'group'];

    protected $fillable = [
        'name_ar',
        'name_en',
        'event_key',
        'enabled',
        'trigger_offset_minutes',
        'escalation_after_minutes',
        'priority',
        'conditions',
        'created_by_user_id',
    ];

    public function localizedName(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return $locale === 'en'
            ? (string) ($this->name_en ?: $this->name_ar)
            : (string) ($this->name_ar ?: $this->name_en);
    }

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'trigger_offset_minutes' => 'integer',
            'escalation_after_minutes' => 'integer',
            'conditions' => 'array',
        ];
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('enabled', true);
    }

    public function channels(): HasMany
    {
        return $this->hasMany(NotificationRuleChannel::class);
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(NotificationRuleRecipient::class);
    }

    public function occurrences(): HasMany
    {
        return $this->hasMany(NotificationOccurrence::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}

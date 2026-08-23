<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'in_app_enabled',
        'mail_enabled',
        'push_enabled',
        'sms_enabled',
        'whatsapp_enabled',
        'quiet_hours_start',
        'quiet_hours_end',
        'minimum_external_priority',
        'daily_email_digest',
        'last_digest_at',
    ];

    protected function casts(): array
    {
        return [
            'in_app_enabled' => 'boolean',
            'mail_enabled' => 'boolean',
            'push_enabled' => 'boolean',
            'sms_enabled' => 'boolean',
            'whatsapp_enabled' => 'boolean',
            'daily_email_digest' => 'boolean',
            'last_digest_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function channelEnabled(string $channel): bool
    {
        return match ($channel) {
            'database' => (bool) ($this->in_app_enabled ?? true),
            'mail' => (bool) ($this->mail_enabled ?? false),
            'push' => (bool) ($this->push_enabled ?? false),
            'sms' => (bool) ($this->sms_enabled ?? false),
            'whatsapp' => (bool) ($this->whatsapp_enabled ?? false),
            default => false,
        };
    }

    public function allowsPriority(string $priority): bool
    {
        $levels = ['normal' => 1, 'important' => 2, 'urgent' => 3];

        return ($levels[$priority] ?? 0)
            >= ($levels[$this->minimum_external_priority] ?? 2);
    }

    public function isQuietAt(CarbonInterface $at, string $timezone): bool
    {
        if ($this->quiet_hours_start === null || $this->quiet_hours_end === null) {
            return false;
        }

        $localTime = $at->copy()->setTimezone($timezone)->format('H:i:s');
        $start = (string) $this->quiet_hours_start;
        $end = (string) $this->quiet_hours_end;

        if ($start === $end) {
            return false;
        }

        return $start < $end
            ? $localTime >= $start && $localTime < $end
            : $localTime >= $start || $localTime < $end;
    }
}

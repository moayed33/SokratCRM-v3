<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationRuleRecipient extends Model
{
    protected $fillable = [
        'notification_rule_id',
        'recipient_type',
        'recipient_id',
    ];

    protected function casts(): array
    {
        return ['recipient_id' => 'integer'];
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(NotificationRule::class, 'notification_rule_id');
    }
}

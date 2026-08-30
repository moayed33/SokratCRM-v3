<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoipExtensionAssignment extends Model
{
    protected $fillable = [
        'user_id',
        'extension',
        'assigned_from',
        'assigned_until',
    ];

    protected function casts(): array
    {
        return [
            'assigned_from' => 'datetime',
            'assigned_until' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

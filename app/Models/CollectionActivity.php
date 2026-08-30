<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectionActivity extends Model
{
    protected $fillable = [
        'collection_case_id',
        'actor_user_id',
        'action',
        'from_status',
        'to_status',
        'notes',
        'old_due_at',
        'new_due_at',
        'old_collector_user_id',
        'new_collector_user_id',
    ];

    protected function casts(): array
    {
        return [
            'old_due_at' => 'datetime',
            'new_due_at' => 'datetime',
        ];
    }

    public function collectionCase(): BelongsTo
    {
        return $this->belongsTo(CollectionCase::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function oldCollector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'old_collector_user_id');
    }

    public function newCollector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'new_collector_user_id');
    }
}

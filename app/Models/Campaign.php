<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
#[Fillable([
    'branch_id',
    'name',
    'image_path',
    'cost',
    'starts_at',
    'ends_at',
    'created_by_user_id',
])]
class Campaign extends Model
{
    protected function casts(): array
    {
        return [
            'cost' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function scopeForBranch(Builder $query, int|string|null $branchId = null, bool $includeGeneral = true): Builder
    {
        if ($branchId !== null && $branchId !== '' && $branchId !== 'all') {
            if ($includeGeneral) {
                return $query->where(function (Builder $subQuery) use ($branchId): void {
                    $subQuery->whereNull('campaigns.branch_id')
                        ->orWhere('campaigns.branch_id', (int) $branchId);
                });
            }

            return $query->where('campaigns.branch_id', (int) $branchId);
        }

        return $query;
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function leads(): BelongsToMany
    {
        return $this->belongsToMany(Lead::class)->withTimestamps();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}

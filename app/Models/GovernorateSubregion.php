<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GovernorateSubregion extends Model
{
    use HasFactory;

    protected $fillable = [
        'governorate_id',
        'code',
        'name_ar',
        'name_en',
        'is_active',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'governorate_id' => 'integer',
            'is_active' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    public function collectors(): HasMany
    {
        return $this->hasMany(User::class, 'collection_subregion_id');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'subregion_id');
    }

    public function collectionCases(): HasMany
    {
        return $this->hasMany(CollectionCase::class, 'subregion_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getNameAttribute(): string
    {
        if (app()->getLocale() === 'en' && ! empty($this->name_en)) {
            return $this->name_en;
        }

        return $this->name_ar;
    }

    public function getFullNameAttribute(): string
    {
        $govName = $this->governorate?->name ?? '';

        return $govName !== '' ? "{$this->name} ({$govName})" : $this->name;
    }
}

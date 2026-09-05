<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Governorate extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name_ar',
        'name_en',
        'is_active',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function subregions(): HasMany
    {
        return $this->hasMany(GovernorateSubregion::class)->orderBy('position')->orderBy('name_ar');
    }

    public function activeSubregions(): HasMany
    {
        return $this->subregions()->where('is_active', true);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function collectionCases(): HasMany
    {
        return $this->hasMany(CollectionCase::class);
    }

    public function collectors(): HasManyThrough
    {
        return $this->hasManyThrough(
            User::class,
            GovernorateSubregion::class,
            'governorate_id',
            'collection_subregion_id',
            'id',
            'id',
        );
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
}

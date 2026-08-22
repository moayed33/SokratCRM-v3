<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Lang;

class PipelineStage extends Model
{
    public const SIDEBAR_CACHE_KEY = 'crm.sidebar.active_pipeline_stages';

    protected static function booted(): void
    {
        static::saved(static function (): void {
            static::clearSidebarCache();
        });

        static::deleted(static function (): void {
            static::clearSidebarCache();
        });
    }

    public static function getActiveStagesForSidebar(): Collection
    {
        $cached = Cache::remember(
            self::SIDEBAR_CACHE_KEY,
            now()->addHours(24),
            static fn () => self::query()
                ->where('is_active', true)
                ->orderBy('position')
                ->orderBy('id')
                ->get(['id', 'code', 'name_ar', 'color', 'icon', 'position', 'is_primary', 'is_active'])
                ->toArray()
        );

        return self::hydrate(is_array($cached) ? $cached : []);
    }

    public static function clearSidebarCache(): void
    {
        Cache::forget(self::SIDEBAR_CACHE_KEY);
    }

    public function localizedName(?string $locale = null): string
    {
        $loc = $locale ?? app()->getLocale();
        if ($loc === 'en') {
            if ($this->code === 'new') {
                return __('crm.status_new');
            }
            if ($this->code === 'donor') {
                return __('crm.donor');
            }
            if ($this->code && Lang::has('crm.stage_'.$this->code)) {
                return __('crm.stage_'.$this->code);
            }
        }

        return (string) $this->name_ar;
    }

    protected $fillable = [
        'code',
        'name_ar',
        'description_ar',
        'position',
        'color',
        'icon',
        'is_primary',
        'is_active',
    ];
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_primary' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function isPrimary(): bool
    {
        return (bool) $this->is_primary;
    }

    public function hasLeads(): bool
    {
        return Lead::query()
            ->whereHas('status', fn ($q) => $q->where('pipeline_stage_id', $this->id))
            ->exists();
    }

    public function leadsCount(): int
    {
        return Lead::query()
            ->whereHas('status', fn ($q) => $q->where('pipeline_stage_id', $this->id))
            ->count();
    }

    public function canBeDeleted(): bool
    {
        return ! $this->isPrimary() && ! $this->hasLeads();
    }
    public function statuses(): HasMany
    {
        return $this->hasMany(LeadStatus::class)
            ->orderBy('position');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Lang;

class PipelineStage extends Model
{
    public const SIDEBAR_CACHE_KEY = 'crm.sidebar.active_pipeline_stages';
    public const DASHBOARD_CACHE_KEY = 'crm.dashboard.active_pipeline_stages';

    protected static function booted(): void
    {
        static::created(static function (PipelineStage $stage): void {
            static::clearSidebarCache();
            $stage->ensureDefaultStatus();
        });

        static::updated(static function (PipelineStage $stage): void {
            static::clearSidebarCache();
            if ($stage->statuses()->count() === 1) {
                $defaultStatus = $stage->statuses()->first();
                if ($defaultStatus !== null) {
                    $defaultStatus->update([
                        'name_ar' => $stage->name_ar,
                        'color' => $stage->color,
                    ]);
                }
            }
        });

        static::deleted(static function (PipelineStage $stage): void {
            static::clearSidebarCache();
            LeadStatus::query()
                ->where('pipeline_stage_id', $stage->id)
                ->whereDoesntHave('leads')
                ->delete();
        });
    }

    public function ensureDefaultStatus(): LeadStatus
    {
        $existing = $this->statuses()->first();
        if ($existing !== null) {
            return $existing;
        }

        $maxPosition = (int) (LeadStatus::query()->max('position') ?? 0);
        $nextPosition = $maxPosition + 1;

        return LeadStatus::query()->create([
            'pipeline_stage_id' => $this->id,
            'code' => $this->code,
            'name_ar' => $this->name_ar,
            'position' => $nextPosition,
            'color' => $this->color ?: '#3478f6',
            'is_terminal' => false,
        ]);
    }

    public static function repairOrphanStages(): int
    {
        $orphans = self::query()->whereDoesntHave('statuses')->get();
        $repaired = 0;
        foreach ($orphans as $orphan) {
            $orphan->ensureDefaultStatus();
            $repaired++;
        }
        return $repaired;
    }

    public static function activeOrdered(): Collection
    {
        return self::query()
            ->where('is_active', true)
            ->orderBy('position')
            ->orderBy('id')
            ->get();
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
        Cache::forget(self::DASHBOARD_CACHE_KEY);
        Cache::forget('crm.dashboard.pipeline_stages');
    }

    public function localizedName(?string $locale = null): string
    {
        $loc = $locale ?? app()->getLocale();
        if ($loc === 'en') {
            if ($this->code === 'new') {
                return __('crm.status_new');
            }
            if ($this->code === 'no_answer' || $this->code === 'no-answer') {
                return __('crm.status_no_answer');
            }
            if ($this->code === 'not_interested' || $this->code === 'not-interested') {
                return __('crm.status_not_interested');
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

    public function leads(): HasManyThrough
    {
        return $this->hasManyThrough(
            Lead::class,
            LeadStatus::class,
            'pipeline_stage_id',
            'lead_status_id',
            'id',
            'id'
        );
    }
}

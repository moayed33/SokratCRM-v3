<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeadStatus extends Model
{
    protected $fillable = [
        'pipeline_stage_id',
        'code',
        'name_ar',
        'position',
        'color',
        'is_terminal',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_terminal' => 'boolean',
        ];
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(
            PipelineStage::class,
            'pipeline_stage_id'
        );
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function localizedName(?string $locale = null): string
    {
        $loc = $locale ?? app()->getLocale();
        if ($loc === 'en') {
            return match ($this->code) {
                'new' => __('crm.status_new'),
                'no_answer', 'no-answer' => __('crm.status_no_answer'),
                'not_interested', 'not-interested' => __('crm.status_not_interested'),
                'donor' => __('crm.donor'),
                default => (string) ($this->name_ar ?? $this->code),
            };
        }

        return (string) $this->name_ar;
    }
}

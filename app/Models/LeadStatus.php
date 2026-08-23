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
}

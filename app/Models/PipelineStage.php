<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PipelineStage extends Model
{
    protected $fillable = [
        'code',
        'name_ar',
        'description_ar',
        'position',
        'color',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function statuses(): HasMany
    {
        return $this->hasMany(LeadStatus::class)
            ->orderBy('position');
    }
}

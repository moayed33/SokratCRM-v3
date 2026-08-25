<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CrmOption extends Model
{
    protected $fillable = [
        'set_key',
        'value',
        'label_ar',
        'label_en',
        'position',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function scopeForSet(Builder $query, string $setKey): Builder
    {
        return $query->where('set_key', mb_strtolower(trim($setKey)))->orderBy('position')->orderBy('id');
    }
}

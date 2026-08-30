<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstantDonationMethod extends Model
{
    protected $fillable = [
        'code',
        'name_ar',
        'name_en',
        'is_active',
        'position',
        'accounts',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'position' => 'integer',
            'accounts' => 'array',
        ];
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    public function localizedName(): string
    {
        return app()->getLocale() === 'en' && filled($this->name_en)
            ? (string) $this->name_en
            : (string) $this->name_ar;
    }

    /**
     * @return list<string>
     */
    public function getAccountsList(): array
    {
        if (empty($this->accounts) || ! is_array($this->accounts)) {
            return [];
        }

        return collect($this->accounts)
            ->map(static fn ($item): string => trim((string) $item))
            ->filter(static fn (string $item): bool => $item !== '')
            ->values()
            ->all();
    }
}

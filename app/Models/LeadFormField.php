<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadFormField extends Model
{
    public const TYPE_TEXT = 'text';

    public const TYPE_TEXTAREA = 'textarea';

    public const TYPE_NUMBER = 'number';

    public const TYPE_DATE = 'date';

    public const TYPE_DATETIME = 'datetime';

    public const TYPE_SELECT = 'select';

    public const TYPE_MULTISELECT = 'multiselect';

    public const TYPE_CHECKBOX = 'checkbox';

    public const TYPE_EMAIL = 'email';

    public const TYPE_TEL = 'tel';

    public const TYPE_URL = 'url';

    public const CUSTOM_TYPES = [
        self::TYPE_TEXT,
        self::TYPE_TEXTAREA,
        self::TYPE_NUMBER,
        self::TYPE_DATE,
        self::TYPE_DATETIME,
        self::TYPE_SELECT,
        self::TYPE_MULTISELECT,
        self::TYPE_CHECKBOX,
        self::TYPE_EMAIL,
        self::TYPE_TEL,
        self::TYPE_URL,
    ];

    public const SECTION_BASIC_INFO = 'basic_info';

    public const SECTION_DONATION_INFO = 'donation_info';

    public const SECTION_CONTACT_FOLLOWUP = 'contact_followup';

    public const SECTION_OTHER = 'other';

    public const SECTIONS = [
        self::SECTION_BASIC_INFO,
        self::SECTION_DONATION_INFO,
        self::SECTION_CONTACT_FOLLOWUP,
        self::SECTION_OTHER,
    ];

    protected $fillable = [
        'entity',
        'key',
        'label_ar',
        'label_en',
        'type',
        'options',
        'is_required',
        'is_active',
        'is_system',
        'is_locked',
        'system_column',
        'show_in_create',
        'show_in_edit',
        'show_in_filter',
        'show_in_table',
        'show_in_export',
        'section',
        'position',
        'help_text_ar',
        'help_text_en',
        'condition_field',
        'condition_value',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'is_system' => 'boolean',
            'is_locked' => 'boolean',
            'show_in_create' => 'boolean',
            'show_in_edit' => 'boolean',
            'show_in_filter' => 'boolean',
            'show_in_table' => 'boolean',
            'show_in_export' => 'boolean',
        ];
    }

    public function scopeForEntity($query, string $entity)
    {
        return $query->where('entity', mb_strtolower(trim($entity)));
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('position')->orderBy('id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->ordered();
    }

    public function isChoice(): bool
    {
        return in_array($this->type, [self::TYPE_SELECT, self::TYPE_MULTISELECT], true);
    }

    public function optionValues(): array
    {
        return collect($this->options ?? [])
            ->pluck('value')
            ->filter(static fn ($value) => $value !== null && $value !== '')
            ->values()
            ->all();
    }

    public function label(): string
    {
        if (app()->getLocale() === 'en' && trim((string) $this->label_en) !== '') {
            return trim((string) $this->label_en);
        }

        return trim((string) $this->label_ar);
    }

    public function helpText(): ?string
    {
        if (app()->getLocale() === 'en') {
            return trim((string) $this->help_text_en) !== '' ? trim((string) $this->help_text_en) : null;
        }

        return trim((string) $this->help_text_ar) !== '' ? trim((string) $this->help_text_ar) : null;
    }
}

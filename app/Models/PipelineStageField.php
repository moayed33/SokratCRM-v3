<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class PipelineStageField extends Model
{
    use SoftDeletes;

    public const CACHE_KEY_PREFIX = 'crm.pipeline_stage_fields.stage_';

    public const TYPES = [
        'text',
        'textarea',
        'number',
        'email',
        'tel',
        'url',
        'date',
        'datetime',
        'select',
        'multiselect',
        'checkbox',
    ];

    public const OPERATORS = [
        'equals',
        'not_equals',
        'is_checked',
        'is_not_checked',
        'is_empty',
        'is_not_empty',
        'in',
        'not_in',
    ];

    protected $fillable = [
        'pipeline_stage_id',
        'key',
        'label_ar',
        'label_en',
        'type',
        'placeholder_ar',
        'placeholder_en',
        'help_text_ar',
        'help_text_en',
        'is_required',
        'options',
        'validation_rules',
        'conditions',
        'show_on_transition',
        'show_on_stage_view',
        'show_in_history',
        'is_active',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'options' => 'array',
            'validation_rules' => 'array',
            'conditions' => 'array',
            'show_on_transition' => 'boolean',
            'show_on_stage_view' => 'boolean',
            'show_in_history' => 'boolean',
            'is_active' => 'boolean',
            'position' => 'integer',
            'deleted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (self $field): void {
            self::flushCache((int) $field->pipeline_stage_id);
        });

        static::deleted(function (self $field): void {
            self::flushCache((int) $field->pipeline_stage_id);
        });

        static::restored(function (self $field): void {
            self::flushCache((int) $field->pipeline_stage_id);
        });
    }

    public static function flushCache(int $stageId): void
    {
        Cache::forget(self::CACHE_KEY_PREFIX . $stageId);
        Cache::forget(self::CACHE_KEY_PREFIX . $stageId . '_all');
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(PipelineStage::class, 'pipeline_stage_id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(LeadStageFieldValue::class, 'pipeline_stage_field_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('id');
    }

    public function scopeForTransition(Builder $query): Builder
    {
        return $query->where('show_on_transition', true);
    }

    public function localizedLabel(?string $locale = null): string
    {
        $currentLocale = $locale ?? app()->getLocale();
        if ($currentLocale === 'en' && ! empty($this->label_en)) {
            return (string) $this->label_en;
        }

        return (string) ($this->label_ar ?: $this->key);
    }

    public function localizedPlaceholder(?string $locale = null): string
    {
        $currentLocale = $locale ?? app()->getLocale();
        if ($currentLocale === 'en' && ! empty($this->placeholder_en)) {
            return (string) $this->placeholder_en;
        }

        return (string) ($this->placeholder_ar ?? '');
    }

    public function localizedHelpText(?string $locale = null): string
    {
        $currentLocale = $locale ?? app()->getLocale();
        if ($currentLocale === 'en' && ! empty($this->help_text_en)) {
            return (string) $this->help_text_en;
        }

        return (string) ($this->help_text_ar ?? '');
    }

    /**
     * Normalized options collection for select/multiselect types.
     * Each item: ['value' => '...', 'label_ar' => '...', 'label_en' => '...']
     */
    public function normalizedOptions(): array
    {
        if (empty($this->options) || ! is_array($this->options)) {
            $result = [];
        } else {
            $result = [];
            foreach ($this->options as $option) {
                if (is_array($option)) {
                    $val = (string) ($option['value'] ?? $option['key'] ?? '');
                    $lblAr = (string) ($option['label_ar'] ?? $option['label'] ?? $val);
                    $lblEn = (string) ($option['label_en'] ?? $lblAr);
                    if ($val !== '') {
                        $result[] = [
                            'value' => $val,
                            'label_ar' => $lblAr,
                            'label_en' => $lblEn,
                        ];
                    }
                } elseif (is_string($option) && trim($option) !== '') {
                    $val = trim($option);
                    $result[] = [
                        'value' => $val,
                        'label_ar' => $val,
                        'label_en' => $val,
                    ];
                }
            }
        }

        if ($this->allowsCustomValue()) {
            $hasOther = false;
            foreach ($result as $item) {
                if (in_array(strtolower((string) $item['value']), ['other', 'custom', 'أخرى'], true)) {
                    $hasOther = true;
                    break;
                }
            }
            if (! $hasOther) {
                $result[] = [
                    'value' => 'other',
                    'label_ar' => 'أخرى',
                    'label_en' => 'Other',
                ];
            }
        }

        return $result;
    }

    /**
     * Determine if this select field allows a custom value (Other / أخرى).
     */
    public function allowsCustomValue(): bool
    {
        if ($this->type !== 'select') {
            return false;
        }

        if (! empty($this->validation_rules['allow_custom']) || ! empty($this->validation_rules['allow_other'])) {
            return true;
        }

        if (is_array($this->options)) {
            foreach ($this->options as $opt) {
                if (is_array($opt)) {
                    $val = strtolower(trim((string) ($opt['value'] ?? $opt['key'] ?? '')));
                    if (in_array($val, ['other', 'custom', 'أخرى'], true)) {
                        return true;
                    }
                } elseif (is_string($opt)) {
                    $val = strtolower(trim($opt));
                    if (in_array($val, ['other', 'custom', 'أخرى'], true)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Determine the custom input type (date, datetime, text).
     */
    public function customInputType(): string
    {
        $configured = $this->validation_rules['custom_type'] ?? $this->validation_rules['custom_input_type'] ?? null;
        if (! empty($configured) && in_array($configured, ['date', 'datetime', 'text'], true)) {
            return (string) $configured;
        }

        $key = strtolower((string) $this->key);
        if (str_contains($key, 'time') || str_contains($key, 'appointment') || str_contains($key, 'callback')) {
            return 'datetime';
        }

        if (str_contains($key, 'date') || str_contains($key, 'donation') || str_contains($key, 'schedule') || str_contains($key, 'cycle') || str_contains($key, 'followup')) {
            return 'date';
        }

        return 'text';
    }

    /**
     * HTML input type attribute for custom input.
     */
    public function customInputHtmlType(): string
    {
        return match ($this->customInputType()) {
            'datetime' => 'datetime-local',
            'text' => 'text',
            default => 'date',
        };
    }

    /**
     * Localized label for custom input field.
     */
    public function customInputLabel(?string $locale = null): string
    {
        $currentLocale = $locale ?? app()->getLocale();
        $type = $this->customInputType();

        if ($type === 'datetime') {
            return $currentLocale === 'en' ? 'Custom Date & Time' : 'موعد ووقت مخصص';
        }
        if ($type === 'text') {
            return $currentLocale === 'en' ? 'Custom Value' : 'قيمة مخصصة';
        }

        return $currentLocale === 'en' ? 'Custom Date' : 'موعد مخصص';
    }

    /**
     * Localized placeholder for custom input field.
     */
    public function customInputPlaceholder(?string $locale = null): string
    {
        $currentLocale = $locale ?? app()->getLocale();
        return $currentLocale === 'en' ? 'Select Date' : 'اختر الموعد';
    }
    /**
     * Compatibility accessor / mutator for 'required' attribute.
     */
    public function getRequiredAttribute(): bool
    {
        return (bool) $this->is_required;
    }

    public function setRequiredAttribute(bool|int|string $value): void
    {
        $this->attributes['is_required'] = (bool) $value;
    }
}

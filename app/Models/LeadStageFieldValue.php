<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadStageFieldValue extends Model
{
    protected $fillable = [
        'lead_id',
        'pipeline_stage_id',
        'pipeline_stage_field_id',
        'lead_status_history_id',
        'field_key',
        'field_type',
        'value',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(PipelineStage::class, 'pipeline_stage_id');
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(PipelineStageField::class, 'pipeline_stage_field_id')->withTrashed();
    }

    public function statusHistory(): BelongsTo
    {
        return $this->belongsTo(LeadStatusHistory::class, 'lead_status_history_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get the decoded typed value.
     */
    public function getTypedValue(): mixed
    {
        $raw = $this->value;
        if ($raw === null) {
            return null;
        }

        if ($this->field_type === 'checkbox') {
            return filter_var($raw, FILTER_VALIDATE_BOOLEAN);
        }

        if ($this->field_type === 'number') {
            return is_numeric($raw) ? (str_contains((string) $raw, '.') ? (float) $raw : (int) $raw) : $raw;
        }

        if ($this->field_type === 'multiselect') {
            $decoded = json_decode((string) $raw, true);
            return is_array($decoded) ? $decoded : (explode(',', (string) $raw));
        }

        // Try JSON decode for structured data
        if (is_string($raw) && (str_starts_with($raw, '{') || str_starts_with($raw, '['))) {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return $raw;
    }

    /**
     * Human-friendly formatted representation of value for display.
     */
    public function formattedValue(?string $locale = null): string
    {
        $currentLocale = $locale ?? app()->getLocale();
        $val = $this->getTypedValue();

        if ($val === null || $val === '') {
            return '—';
        }

        if ($this->field_type === 'checkbox') {
            $isTrue = (bool) $val;
            if ($currentLocale === 'en') {
                return $isTrue ? 'Yes' : 'No';
            }
            return $isTrue ? 'نعم' : 'لا';
        }

        if ($this->field_type === 'date') {
            try {
                return Carbon::parse((string) $val)->format('Y-m-d');
            } catch (\Throwable) {
                return (string) $val;
            }
        }

        if ($this->field_type === 'datetime') {
            try {
                return Carbon::parse((string) $val)->format('Y-m-d H:i');
            } catch (\Throwable) {
                return (string) $val;
            }
        }

        if ($this->field_type === 'select' && $this->field) {
            $options = $this->field->normalizedOptions();
            foreach ($options as $opt) {
                if ((string) $opt['value'] === (string) $val) {
                    return $currentLocale === 'en' ? $opt['label_en'] : $opt['label_ar'];
                }
            }
        }

        if ($this->field_type === 'multiselect' && is_array($val)) {
            $labels = [];
            $options = $this->field ? $this->field->normalizedOptions() : [];
            $optionMap = [];
            foreach ($options as $opt) {
                $optionMap[$opt['value']] = $currentLocale === 'en' ? $opt['label_en'] : $opt['label_ar'];
            }

            foreach ($val as $item) {
                $labels[] = $optionMap[(string) $item] ?? (string) $item;
            }

            return implode(', ', $labels);
        }

        if (is_array($val)) {
            return json_encode($val, JSON_UNESCAPED_UNICODE);
        }

        return (string) $val;
    }
}

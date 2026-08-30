<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Donation extends Model
{
    protected $fillable = [
        'lead_id',
        'lead_followup_id',
        'donation_type_id',
        'donation_type',
        'amount',
        'cycle',
        'donation_way',
        'instant_donation_method_id',
        'instant_donation_account',
        'collection_case_id',
        'receipt_path',
        'receipt_original_name',
        'donated_at',
        'recorded_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'donated_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function followup(): BelongsTo
    {
        return $this->belongsTo(LeadFollowup::class, 'lead_followup_id');
    }

    public function donationType(): BelongsTo
    {
        return $this->belongsTo(DonationType::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    public function instantDonationMethod(): BelongsTo
    {
        return $this->belongsTo(InstantDonationMethod::class);
    }

    public function collectionCase(): BelongsTo
    {
        return $this->belongsTo(CollectionCase::class);
    }
}

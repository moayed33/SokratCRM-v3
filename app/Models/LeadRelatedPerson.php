<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadRelatedPerson extends Model
{
    protected $table = 'lead_related_people';

    protected $fillable = [
        'lead_id',
        'name',
        'phone',
        'relationship_type',
        'notes',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}

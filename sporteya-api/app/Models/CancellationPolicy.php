<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CancellationPolicy extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'venue_id',
        'full_refund_hours',
        'partial_refund_hours',
        'partial_refund_pct',
        'policy_text',
    ];

    protected $casts = [
        'full_refund_hours' => 'integer',
        'partial_refund_hours' => 'integer',
        'partial_refund_pct' => 'decimal:2',
    ];

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }
}

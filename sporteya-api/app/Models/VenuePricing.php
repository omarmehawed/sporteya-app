<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VenuePricing extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'venue_pricing';

    protected $fillable = [
        'venue_id',
        'sport_id',
        'day_of_week',
        'start_time',
        'end_time',
        'price_per_hour',
        'currency',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'price_per_hour' => 'decimal:2',
    ];

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }
}

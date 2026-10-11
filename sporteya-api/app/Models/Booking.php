<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'lobby_id',
        'venue_id',
        'total_amount',
        'platform_fee_pct',
        'platform_fee',
        'venue_amount',
        'currency',
        'status',
        'confirmation_code',
        'qr_token',
        'policy_snapshot',
        'cancelled_at',
        'cancellation_reason',
        'cancelled_by',
    ];

    protected $hidden = [
        'qr_token', // Never expose qr_token in list endpoints
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'platform_fee_pct' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'venue_amount' => 'decimal:2',
        'policy_snapshot' => 'array',
        'cancelled_at' => 'datetime',
    ];

    public function lobby(): BelongsTo
    {
        return $this->belongsTo(MatchLobby::class, 'lobby_id');
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    // Correction G: Booking -> Payments (1:N via booking_id, nullable until confirmed)
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'booking_id');
    }

    public function review(): HasOne
    {
        return $this->hasOne(VenueReview::class, 'booking_id');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MatchLobby extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'creator_id',
        'venue_id',
        'sport_id',
        'booking_group_id',
        'match_type',
        'required_players',
        'match_date',
        'start_time',
        'end_time',
        'status',
        'invite_code',
        'notes',
        'cancellation_reason',
        'cancelled_at',
        'policy_snapshot',
        'expires_at',
    ];

    protected $casts = [
        'required_players' => 'integer',
        'match_date' => 'date',
        'cancelled_at' => 'datetime',
        'expires_at' => 'datetime',
        'policy_snapshot' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(PlayerProfile::class, 'creator_id');
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    public function bookingGroup(): BelongsTo
    {
        return $this->belongsTo(BookingGroup::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(MatchParticipant::class, 'lobby_id');
    }

    public function booking(): HasOne
    {
        return $this->hasOne(Booking::class, 'lobby_id');
    }

    // Correction G: MatchLobby -> Payments (1:N via lobby_id)
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'lobby_id');
    }

    public function result(): HasOne
    {
        return $this->hasOne(MatchResult::class, 'lobby_id');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['forming', 'payment_pending', 'confirmed']);
    }
}

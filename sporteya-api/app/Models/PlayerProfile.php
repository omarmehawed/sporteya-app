<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlayerProfile extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'username',
        'phone',
        'avatar_url',
        'bio',
        'player_status',
        'onboarding_done',
        // Note: 'location' is managed purely via raw SQL DB statements (Correction 5)
    ];

    protected $casts = [
        'onboarding_done' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function playerSports(): HasMany
    {
        return $this->hasMany(PlayerSport::class, 'player_id');
    }

    public function bookingGroupMembers(): HasMany
    {
        return $this->hasMany(BookingGroupMember::class, 'player_id');
    }

    public function matchParticipants(): HasMany
    {
        return $this->hasMany(MatchParticipant::class, 'player_id');
    }

    public function createdLobbies(): HasMany
    {
        return $this->hasMany(MatchLobby::class, 'creator_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(VenueReview::class, 'player_id');
    }

    public function scopeAvailable($query)
    {
        return $query->where('player_status', 'available');
    }
}

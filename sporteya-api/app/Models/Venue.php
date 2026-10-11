<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Venue extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'owner_id',
        'name',
        'description',
        'address',
        'city',
        'phone',
        'has_lighting',
        'has_changing_room',
        'has_parking',
        'is_active',
        'is_verified',
        'verified_at',
        'rating_avg',
        'reviews_count',
        'location',
    ];

    protected $casts = [
        'has_lighting' => 'boolean',
        'has_changing_room' => 'boolean',
        'has_parking' => 'boolean',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'rating_avg' => 'decimal:2',
        'reviews_count' => 'integer',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(VenueMedia::class)->orderBy('sort_order');
    }

    public function sports(): HasMany
    {
        return $this->hasMany(VenueSport::class);
    }

    public function pricing(): HasMany
    {
        return $this->hasMany(VenuePricing::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(VenueSchedule::class);
    }

    public function blockedSlots(): HasMany
    {
        return $this->hasMany(VenueBlockedSlot::class);
    }

    public function cancellationPolicy(): HasOne
    {
        return $this->hasOne(CancellationPolicy::class);
    }

    public function lobbies(): HasMany
    {
        return $this->hasMany(MatchLobby::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(VenueReview::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sport extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name_ar',
        'name_en',
        'slug',
        'icon',
        'default_players_per_side',
        'is_active',
    ];

    protected $casts = [
        'default_players_per_side' => 'integer',
        'is_active' => 'boolean',
    ];

    public function playerSports(): HasMany
    {
        return $this->hasMany(PlayerSport::class);
    }

    public function venueSports(): HasMany
    {
        return $this->hasMany(VenueSport::class);
    }

    public function matchLobbies(): HasMany
    {
        return $this->hasMany(MatchLobby::class);
    }

    public function bookingGroups(): HasMany
    {
        return $this->hasMany(BookingGroup::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

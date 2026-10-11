<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookingGroup extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'creator_id',
        'sport_id',
        'name',
        'description',
        'invite_code',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(PlayerProfile::class, 'creator_id');
    }

    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(BookingGroupMember::class);
    }

    public function matchLobbies(): HasMany
    {
        return $this->hasMany(MatchLobby::class);
    }
}

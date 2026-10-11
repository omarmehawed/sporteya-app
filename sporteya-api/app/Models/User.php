<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'fcm_token',
        'google_id',
        'setup_complete',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'setup_complete' => 'boolean',
        ];
    }

    public function playerProfile(): HasOne
    {
        return $this->hasOne(PlayerProfile::class, 'user_id');
    }

    public function venues(): HasMany
    {
        return $this->hasMany(Venue::class, 'owner_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'user_id');
    }

    public function isPlayer(): bool
    {
        return $this->role === 'player';
    }

    public function isVenueOwner(): bool
    {
        return $this->role === 'venue_owner';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}

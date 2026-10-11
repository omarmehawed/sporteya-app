<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchParticipant extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'lobby_id',
        'player_id',
        'invite_status',
        'payment_status',
        'payment_amount',
        'invited_by',
        'checked_in_at',
    ];

    protected $casts = [
        'payment_amount' => 'decimal:2',
        'checked_in_at' => 'datetime',
    ];

    public function lobby(): BelongsTo
    {
        return $this->belongsTo(MatchLobby::class, 'lobby_id');
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(PlayerProfile::class, 'player_id');
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(PlayerProfile::class, 'invited_by');
    }

    public function scopeAccepted($query)
    {
        return $query->where('invite_status', 'accepted');
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }
}

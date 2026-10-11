<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'lobby_id',
        'player_id',
        'booking_id',
        'amount',
        'currency',
        'status',
        'payment_method',
        'paymob_order_id',
        'paymob_txn_id',
        'paid_at',
        'refunded_at',
        'refund_amount',
    ];

    protected $hidden = [
        'paymob_txn_id', // Never expose raw transaction ID in public lists
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    public function lobby(): BelongsTo
    {
        return $this->belongsTo(MatchLobby::class, 'lobby_id');
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(PlayerProfile::class, 'player_id');
    }

    // Correction G: belongsTo Booking (nullable until confirmed)
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}

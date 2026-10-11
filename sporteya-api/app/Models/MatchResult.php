<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchResult extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'lobby_id',
        'recorded_by',
        'score',
        'winner',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function lobby(): BelongsTo
    {
        return $this->belongsTo(MatchLobby::class, 'lobby_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(PlayerProfile::class, 'recorded_by');
    }
}

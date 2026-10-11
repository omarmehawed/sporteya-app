<?php

namespace App\Events;

use App\Models\MatchLobby;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LobbyStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public MatchLobby $lobby;

    public function __construct(MatchLobby $lobby)
    {
        $this->lobby = $lobby;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("lobby.{$this->lobby->id}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'lobby_id' => $this->lobby->id,
            'status' => $this->lobby->status,
            'cancellation_reason' => $this->lobby->cancellation_reason,
            'cancelled_at' => $this->lobby->cancelled_at,
        ];
    }
}

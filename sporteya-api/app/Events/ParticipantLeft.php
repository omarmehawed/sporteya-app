<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ParticipantLeft implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $lobbyId;
    public string $participantId;

    public function __construct(string $lobbyId, string $participantId)
    {
        $this->lobbyId = $lobbyId;
        $this->participantId = $participantId;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("lobby.{$this->lobbyId}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'participant_id' => $this->participantId,
        ];
    }
}

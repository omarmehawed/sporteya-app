<?php

namespace App\Events;

use App\Models\MatchParticipant;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ParticipantJoined implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public MatchParticipant $participant;

    public function __construct(MatchParticipant $participant)
    {
        $this->participant = $participant;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("lobby.{$this->participant->lobby_id}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'participant' => $this->participant->load('player.user:id,name'),
        ];
    }
}

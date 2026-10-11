<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class UpdatePlayerStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Pass 1: mark 'booked' for confirmed matches starting within 2h today
        DB::statement("
            UPDATE player_profiles pp SET player_status = 'booked'
            FROM match_participants mp
            JOIN match_lobbies ml ON ml.id = mp.lobby_id
            WHERE mp.player_id = pp.id
              AND mp.invite_status = 'accepted'
              AND pp.player_status != 'booked'
              AND ml.status = 'confirmed'
              AND ml.match_date = CURRENT_DATE
              AND ml.start_time <= (NOW() + INTERVAL '2 hours')::time
        ");

        // Pass 2 (Correction I): only release if player has NO other confirmed upcoming lobby
        DB::statement("
            UPDATE player_profiles pp SET player_status = 'available'
            FROM match_participants mp
            JOIN match_lobbies ml ON ml.id = mp.lobby_id
            WHERE mp.player_id = pp.id
              AND ml.status = 'completed'
              AND pp.player_status = 'booked'
              AND NOT EXISTS (
                  SELECT 1
                  FROM match_participants mp2
                  JOIN match_lobbies ml2 ON ml2.id = mp2.lobby_id
                  WHERE mp2.player_id = pp.id
                    AND mp2.invite_status = 'accepted'
                    AND ml2.status = 'confirmed'
                    AND (
                      ml2.match_date > CURRENT_DATE
                      OR (ml2.match_date = CURRENT_DATE
                          AND ml2.start_time > NOW()::time)
                    )
              )
        ");
    }
}

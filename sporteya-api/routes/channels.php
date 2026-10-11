<?php

use App\Models\MatchLobby;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
*/

// Authorize matching user for private user notifications
Broadcast::channel('user.{userId}', function (User $user, string $userId) {
    return (string) $user->id === (string) $userId;
});

// Authorize participants and creator for private lobby updates
Broadcast::channel('lobby.{lobbyId}', function (User $user, string $lobbyId) {
    $profile = $user->playerProfile;
    if (!$profile) {
        return false;
    }

    return MatchLobby::where('id', $lobbyId)
        ->where(function ($query) use ($profile) {
            $query->where('creator_id', $profile->id)
                  ->orWhereHas('participants', function ($q) use ($profile) {
                      $q->where('player_id', $profile->id);
                  });
        })
        ->exists();
});

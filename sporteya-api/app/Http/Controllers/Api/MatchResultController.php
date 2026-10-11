<?php

namespace App\Http\Controllers\Api;

use App\Events\LobbyStatusChanged;
use App\Http\Controllers\Controller;
use App\Jobs\SendNotificationJob;
use App\Jobs\UpdatePlayerStatusJob;
use App\Models\MatchLobby;
use App\Models\MatchParticipant;
use App\Models\MatchResult;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MatchResultController extends Controller
{
    /**
     * Submit match result (creator only) and complete lobby.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lobby_id' => ['required', 'uuid', 'exists:match_lobbies,id'],
            'team_a_score' => ['required', 'integer', 'min:0'],
            'team_b_score' => ['required', 'integer', 'min:0'],
            'mvp_player_id' => ['nullable', 'uuid', 'exists:player_profiles,id'],
            'summary' => ['nullable', 'string', 'max:500'],
        ]);

        $profile = $request->user()->playerProfile;
        if (!$profile) {
            return response()->json(['message' => 'ملف اللاعب غير موجود'], 404);
        }

        $lobby = MatchLobby::findOrFail($validated['lobby_id']);

        if ($lobby->creator_id !== $profile->id) {
            return response()->json(['message' => 'منشئ المباراة فقط يمكنه تسجيل النتيجة'], 403);
        }

        if ($lobby->status === 'completed') {
            return response()->json(['message' => 'تم تسجيل نتيجة هذه المباراة مسبقاً'], 422);
        }

        $teamAScore = $validated['team_a_score'];
        $teamBScore = $validated['team_b_score'];
        $scoreString = "{$teamAScore} - {$teamBScore}";
        $winner = $teamAScore > $teamBScore ? 'Team A' : ($teamBScore > $teamAScore ? 'Team B' : 'Draw');

        $result = DB::transaction(function () use ($validated, $lobby, $profile, $scoreString, $winner) {
            $result = MatchResult::create([
                'lobby_id' => $lobby->id,
                'recorded_by' => $profile->id,
                'score' => $scoreString,
                'winner' => $winner,
                'details' => [
                    'team_a_score' => $validated['team_a_score'],
                    'team_b_score' => $validated['team_b_score'],
                    'mvp_player_id' => $validated['mvp_player_id'] ?? null,
                    'summary' => $validated['summary'] ?? null,
                ],
            ]);

            $lobby->update(['status' => 'completed']);
            event(new LobbyStatusChanged($lobby->fresh()));

            return $result;
        });

        // Dispatch UpdatePlayerStatusJob to release players
        UpdatePlayerStatusJob::dispatch();

        // Dispatch delayed review reminder (Correction D)
        $participantUserIds = DB::table('match_participants')
            ->join('player_profiles', 'player_profiles.id', '=', 'match_participants.player_id')
            ->where('match_participants.lobby_id', $lobby->id)
            ->where('match_participants.invite_status', 'accepted')
            ->pluck('player_profiles.user_id')
            ->toArray();

        $dateStr = Carbon::parse($lobby->match_date)->format('Y-m-d');
        $delay = Carbon::parse("{$dateStr} {$lobby->end_time}")
            ->addMinutes(30)
            ->diffInSeconds(now(), false);

        SendNotificationJob::dispatch(
            $participantUserIds,
            'review_reminder',
            ['lobby_id' => $lobby->id]
        )->delay(max(0, abs($delay)));

        return response()->json(['result' => $result], 201);
    }
}

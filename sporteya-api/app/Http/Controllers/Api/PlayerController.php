<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MatchParticipant;
use App\Models\PlayerProfile;
use App\Services\MediaService;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlayerController extends Controller
{
    /**
     * Get authenticated player's full profile with stats.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user->playerProfile;

        if (!$profile) {
            return response()->json(['message' => 'ملف اللاعب غير موجود'], 404);
        }

        $profile->load(['playerSports.sport']);

        $matchesPlayed = MatchParticipant::where('player_id', $profile->id)
            ->where('invite_status', 'accepted')
            ->whereHas('lobby', function ($q) {
                $q->where('status', 'completed');
            })
            ->count();

        // Calculate attendance rate (attended vs confirmed invitations)
        $totalConfirmed = MatchParticipant::where('player_id', $profile->id)
            ->where('invite_status', 'accepted')
            ->count();

        $attendanceRate = $totalConfirmed > 0
            ? round(($matchesPlayed / $totalConfirmed) * 100, 2)
            : 100.0;

        return response()->json([
            'player' => $profile,
            'stats' => [
                'matches_played' => $matchesPlayed,
                'attendance_rate' => $attendanceRate,
            ],
        ], 200);
    }

    /**
     * Update authenticated player's profile.
     * Username update guarded strictly by DB unique constraint (Correction F).
     */
    public function update(Request $request): JsonResponse
    {
        $profile = $request->user()->playerProfile;

        if (!$profile) {
            return response()->json(['message' => 'ملف اللاعب غير موجود'], 404);
        }

        $validated = $request->validate([
            'username' => ['nullable', 'string', 'min:3', 'max:30', 'regex:/^[a-zA-Z0-9_]{3,30}$/'],
            'bio' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'preferred_foot' => ['nullable', 'in:right,left,both'],
        ]);

        try {
            DB::transaction(function () use ($profile, $validated) {
                $fieldsToUpdate = collect($validated)->only(['bio', 'city', 'preferred_foot'])->filter()->all();

                if (isset($validated['username'])) {
                    $fieldsToUpdate['username'] = $validated['username'];
                }

                if (!empty($fieldsToUpdate)) {
                    $profile->update($fieldsToUpdate);
                }

                if (isset($validated['latitude']) && isset($validated['longitude'])) {
                    DB::statement(
                        "UPDATE player_profiles SET location = ST_MakePoint(?, ?)::geography WHERE id = ?",
                        [$validated['longitude'], $validated['latitude'], $profile->id]
                    );
                }
            });
        } catch (UniqueConstraintViolationException $e) {
            return response()->json(['message' => 'هذا الاسم مأخوذ'], 409);
        } catch (QueryException $e) {
            if ($e->getCode() === '23505') {
                return response()->json(['message' => 'هذا الاسم مأخوذ'], 409);
            }
            throw $e;
        }

        return response()->json(['player' => $profile->fresh()->load('playerSports.sport')], 200);
    }

    /**
     * Upload player avatar.
     */
    public function updateAvatar(Request $request, MediaService $mediaService): JsonResponse
    {
        $request->validate([
            'avatar' => ['required', 'file', 'image', 'mimes:jpeg,png,webp,jpg', 'max:5120'],
        ]);

        $profile = $request->user()->playerProfile;

        if (!$profile) {
            return response()->json(['message' => 'ملف اللاعب غير موجود'], 404);
        }

        $avatarUrl = $mediaService->uploadAvatar($request->file('avatar'));
        $profile->update(['avatar_url' => $avatarUrl]);

        return response()->json(['avatar_url' => $avatarUrl], 200);
    }

    /**
     * Update player availability status.
     */
    public function updateStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'player_status' => ['required', 'in:available,booked,inactive'],
        ]);

        $profile = $request->user()->playerProfile;

        if (!$profile) {
            return response()->json(['message' => 'ملف اللاعب غير موجود'], 404);
        }

        $profile->update(['player_status' => $validated['player_status']]);

        return response()->json(['player_status' => $profile->player_status], 200);
    }

    /**
     * Public profile lookup and username availability check.
     */
    public function show(string $username): JsonResponse
    {
        $profile = PlayerProfile::where('username', $username)
            ->with(['playerSports.sport', 'user:id,name'])
            ->first();

        if (!$profile) {
            return response()->json(['message' => 'اللاعب غير موجود'], 404);
        }

        return response()->json(['player' => $profile], 200);
    }
}

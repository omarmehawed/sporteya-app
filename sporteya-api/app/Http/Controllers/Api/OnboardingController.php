<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlayerProfile;
use App\Models\PlayerSport;
use App\Services\MediaService;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OnboardingController extends Controller
{
    /**
     * Bulk upsert player sports.
     */
    public function sports(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sports' => ['required', 'array', 'min:1'],
            'sports.*.sport_id' => ['required', 'uuid', 'exists:sports,id'],
            'sports.*.skill_level' => ['required', 'in:beginner,intermediate,advanced,pro'],
            'sports.*.is_preferred' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();
        $profile = $user->playerProfile;

        if (!$profile) {
            return response()->json(['message' => 'ملف اللاعب غير موجود'], 404);
        }

        DB::transaction(function () use ($profile, $validated) {
            foreach ($validated['sports'] as $sportData) {
                PlayerSport::updateOrCreate(
                    [
                        'player_id' => $profile->id,
                        'sport_id' => $sportData['sport_id'],
                    ],
                    [
                        'skill_level' => $sportData['skill_level'],
                        'is_preferred' => $sportData['is_preferred'] ?? false,
                    ]
                );
            }
        });

        $playerSports = PlayerSport::with('sport')
            ->where('player_id', $profile->id)
            ->get();

        return response()->json(['player_sports' => $playerSports], 200);
    }

    /**
     * Choose or update username during onboarding (Correction F).
     * Never pre-checks with SELECT; DB unique constraint is the guard.
     */
    public function username(Request $request): JsonResponse
    {
        $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[a-zA-Z0-9_]{3,30}$/'],
        ]);

        $profile = $request->user()->playerProfile;

        if (!$profile) {
            return response()->json(['message' => 'ملف اللاعب غير موجود'], 404);
        }

        try {
            DB::transaction(function () use ($profile, $request) {
                $profile->update(['username' => $request->username]);
            });
        } catch (UniqueConstraintViolationException $e) {
            return response()->json(['message' => 'هذا الاسم مأخوذ'], 422);
        } catch (QueryException $e) {
            if ($e->getCode() === '23505') {
                return response()->json(['message' => 'هذا الاسم مأخوذ'], 422);
            }
            throw $e;
        }

        return response()->json(['username' => $profile->fresh()->username], 200);
    }

    /**
     * Set location during onboarding (PostGIS raw query).
     */
    public function location(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'city' => ['nullable', 'string', 'max:100'],
        ]);

        $profile = $request->user()->playerProfile;

        if (!$profile) {
            return response()->json(['message' => 'ملف اللاعب غير موجود'], 404);
        }

        if (isset($validated['latitude']) && isset($validated['longitude'])) {
            DB::statement(
                "UPDATE player_profiles SET location = ST_MakePoint(?, ?)::geography, city = ? WHERE id = ?",
                [
                    $validated['longitude'],
                    $validated['latitude'],
                    $validated['city'] ?? $profile->city,
                    $profile->id,
                ]
            );
        } elseif (isset($validated['city'])) {
            $profile->update(['city' => $validated['city']]);
        }

        return response()->json(['player_profile' => $profile->fresh()], 200);
    }

    /**
     * Upload player avatar during onboarding.
     */
    public function avatar(Request $request, MediaService $mediaService): JsonResponse
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
     * Mark onboarding complete (Correction B: atomic transaction with user setup_complete).
     */
    public function complete(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user->playerProfile;

        if (!$profile) {
            return response()->json(['message' => 'ملف اللاعب غير موجود'], 404);
        }

        DB::transaction(function () use ($user, $profile) {
            $profile->update(['onboarding_done' => true]);
            $user->update(['setup_complete' => true]);
        });

        return response()->json([
            'player_profile' => $profile->fresh(),
            'setup_complete' => true,
        ], 200);
    }
}

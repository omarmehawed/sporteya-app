<?php

namespace App\Services;

use App\Models\PlayerProfile;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;

class UsernameService
{
    /**
     * Pure candidate builder — no DB calls (Correction F).
     */
    public static function buildCandidate(string $name, int $attempt = 0): string
    {
        $base = Str::slug($name, '_');
        if (empty($base)) {
            $base = 'player';
        }

        if ($attempt === 0) {
            return $base;
        }

        if ($attempt < 10) {
            return $base . '_' . rand(1000, 9999);
        }

        // Fallback: microseconds suffix (virtually guaranteed unique)
        return $base . '_' . substr(str_replace('.', '', (string) microtime(true)), -7);
    }

    /**
     * Create player profile with retry loop wrapping actual INSERT (Correction F).
     */
    public static function createProfileForUser(User $user, array $attributes = []): PlayerProfile
    {
        $maxAttempts = 15;

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $candidate = self::buildCandidate($user->name, $attempt);

            try {
                return \Illuminate\Support\Facades\DB::transaction(function () use ($user, $candidate, $attributes) {
                    return PlayerProfile::create(array_merge($attributes, [
                        'user_id' => $user->id,
                        'username' => $candidate,
                        'player_status' => $attributes['player_status'] ?? 'available',
                        'onboarding_done' => $attributes['onboarding_done'] ?? false,
                    ]));
                });
            } catch (UniqueConstraintViolationException $e) {
                if ($attempt === $maxAttempts - 1) {
                    throw $e;
                }
                // Collision: continue to next attempt
            }
        }

        throw new \RuntimeException('Failed to generate unique username after max attempts.');
    }
}

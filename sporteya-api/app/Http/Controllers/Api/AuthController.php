<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UsernameService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    /**
     * Register a new user (player or venue_owner).
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in(['player', 'venue_owner'])],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'status' => 'active',
            'setup_complete' => false,
        ]);

        $profile = null;
        if ($user->role === 'player') {
            // Correction F: Username auto-generation via candidate builder + INSERT retry in caller
            $profile = UsernameService::createProfileForUser($user);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user->fresh(),
            'profile' => $profile,
        ], 201);
    }

    /**
     * Login with email and password (rate limited).
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['البريد الإلكتروني أو كلمة المرور غير صحيحة.'],
            ]);
        }

        if ($user->status === 'suspended') {
            return response()->json([
                'message' => 'الحساب موقوف. يرجى التواصل مع الدعم الفني.',
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        $user->load(['playerProfile']);
        if ($user->role === 'venue_owner') {
            $user->load(['venues']);
        }

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }

    /**
     * Google OAuth login / registration (Correction K: Admins rejected).
     */
    public function google(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['nullable', 'string'],
            'google_id' => ['nullable', 'string'],
            'email' => ['nullable', 'string', 'email'],
            'name' => ['nullable', 'string'],
            'avatar' => ['nullable', 'string'],
        ]);

        $googleId = null;
        $email = null;
        $name = null;
        $avatar = null;

        // If client sends token (Socialite verification) or direct payload
        if ($request->filled('token')) {
            try {
                $googleUser = Socialite::driver('google')->userFromToken($request->input('token'));
                $googleId = $googleUser->getId();
                $email = $googleUser->getEmail();
                $name = $googleUser->getName();
                $avatar = $googleUser->getAvatar();
            } catch (\Exception $e) {
                return response()->json(['message' => 'Invalid Google token.'], 401);
            }
        } else {
            $googleId = $request->input('google_id');
            $email = $request->input('email');
            $name = $request->input('name');
            $avatar = $request->input('avatar');
        }

        if (! $googleId || ! $email) {
            return response()->json(['message' => 'Missing Google profile information.'], 422);
        }

        $user = User::where('google_id', $googleId)
            ->orWhere('email', $email)
            ->first();

        // Correction K3: Admin cannot log in via Google OAuth
        if ($user && $user->role === 'admin') {
            return response()->json([
                'message' => 'Admin must use password login.',
            ], 403);
        }

        if (! $user) {
            $user = User::create([
                'name' => $name ?? 'Player',
                'email' => $email,
                'google_id' => $googleId,
                'role' => 'player',
                'status' => 'active',
                'setup_complete' => false,
            ]);

            // Auto-generate username on first sign-in using INSERT retry loop (Correction F)
            UsernameService::createProfileForUser($user, [
                'avatar_url' => $avatar,
            ]);
        } else {
            if (empty($user->google_id)) {
                $user->update(['google_id' => $googleId]);
            }
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        $user->load(['playerProfile']);

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }

    /**
     * Get authenticated user profile & setup status (Correction B).
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load(['playerProfile']);

        if ($user->role === 'venue_owner') {
            $user->load(['venues']);
        }

        return response()->json([
            'user' => $user,
            'setup_complete' => (bool) $user->setup_complete,
        ]);
    }

    /**
     * Logout and revoke current token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }
}

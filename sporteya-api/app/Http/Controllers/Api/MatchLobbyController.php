<?php

namespace App\Http\Controllers\Api;

use App\Events\LobbyStatusChanged;
use App\Events\ParticipantJoined;
use App\Events\ParticipantLeft;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessRefundJob;
use App\Jobs\SendNotificationJob;
use App\Models\BookingGroupMember;
use App\Models\CancellationPolicy;
use App\Models\MatchLobby;
use App\Models\MatchParticipant;
use App\Models\Payment;
use App\Models\PlayerProfile;
use App\Models\Venue;
use App\Models\VenueBlockedSlot;
use App\Models\VenueSport;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MatchLobbyController extends Controller
{
    /**
     * Create a new match lobby (Phase B6, Corrections 4, C, V).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'venue_id' => ['required', 'uuid', 'exists:venues,id'],
            'sport_id' => ['required', 'uuid', 'exists:sports,id'],
            'booking_group_id' => ['nullable', 'uuid', 'exists:booking_groups,id'],
            'match_type' => ['required', 'in:private,public,mixed'],
            'required_players' => ['required', 'integer', 'min:2', 'max:50'],
            'match_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i:s,H:i'],
            'end_time' => ['required', 'date_format:H:i:s,H:i', 'after:start_time'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $profile = $request->user()->playerProfile;
        if (!$profile) {
            return response()->json(['message' => 'ملف اللاعب غير موجود'], 404);
        }

        // Standardize time strings to H:i:s
        $startTime = Carbon::parse($validated['start_time'])->format('H:i:s');
        $endTime = Carbon::parse($validated['end_time'])->format('H:i:s');

        // 1. Verify venue supports sport
        $supportsSport = VenueSport::where('venue_id', $validated['venue_id'])
            ->where('sport_id', $validated['sport_id'])
            ->exists();

        if (!$supportsSport) {
            return response()->json(['message' => 'الملعب لا يدعم الرياضة المختارة'], 422);
        }

        // 2. Check blocked slots
        $isBlocked = VenueBlockedSlot::where('venue_id', $validated['venue_id'])
            ->where('date', $validated['match_date'])
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime)
            ->exists();

        if ($isBlocked) {
            return response()->json(['message' => 'هذا الموعد محجوز للصيانة أو مغلق'], 422);
        }

        // 3. Check confirmed bookings (forming lobbies do NOT block creation)
        $isBooked = DB::table('bookings')
            ->join('match_lobbies', 'bookings.lobby_id', '=', 'match_lobbies.id')
            ->where('match_lobbies.venue_id', $validated['venue_id'])
            ->where('match_lobbies.match_date', $validated['match_date'])
            ->where('bookings.status', 'confirmed')
            ->where('match_lobbies.start_time', '<', $endTime)
            ->where('match_lobbies.end_time', '>', $startTime)
            ->exists();

        if ($isBooked) {
            return response()->json(['message' => 'هذا الموعد محجوز بالفعل'], 422);
        }

        // 4. Capture policy snapshot at lobby creation time
        $policy = CancellationPolicy::where('venue_id', $validated['venue_id'])->first();
        $policySnapshot = $policy ? [
            'full_refund_hours' => $policy->full_refund_hours,
            'half_refund_hours' => $policy->half_refund_hours,
            'no_refund_hours' => $policy->no_refund_hours,
            'custom_rules' => $policy->custom_rules,
        ] : [
            'full_refund_hours' => 24,
            'half_refund_hours' => 12,
            'no_refund_hours' => 2,
            'custom_rules' => null,
        ];

        // 5. Create lobby & participants in a transaction
        $lobby = DB::transaction(function () use ($profile, $validated, $startTime, $endTime, $policySnapshot) {
            $inviteCode = Str::upper(Str::random(10));
            while (MatchLobby::where('invite_code', $inviteCode)->exists()) {
                $inviteCode = Str::upper(Str::random(10));
            }

            $lobby = MatchLobby::create([
                'creator_id' => $profile->id,
                'venue_id' => $validated['venue_id'],
                'sport_id' => $validated['sport_id'],
                'booking_group_id' => $validated['booking_group_id'] ?? null,
                'match_type' => $validated['match_type'],
                'required_players' => $validated['required_players'],
                'match_date' => $validated['match_date'],
                'start_time' => $startTime,
                'end_time' => $endTime,
                'notes' => $validated['notes'] ?? null,
                'status' => 'forming',
                'invite_code' => $inviteCode,
                'policy_snapshot' => $policySnapshot,
                'expires_at' => Carbon::now()->addHours(2), // Correction 4
            ]);

            // Add creator as first accepted participant
            MatchParticipant::create([
                'lobby_id' => $lobby->id,
                'player_id' => $profile->id,
                'invite_status' => 'accepted',
                'payment_status' => 'pending',
            ]);

            // Auto-invite group members if group provided
            if (!empty($validated['booking_group_id'])) {
                $members = BookingGroupMember::where('booking_group_id', $validated['booking_group_id'])
                    ->where('player_id', '!=', $profile->id)
                    ->with('player')
                    ->get();

                foreach ($members as $member) {
                    MatchParticipant::create([
                        'lobby_id' => $lobby->id,
                        'player_id' => $member->player_id,
                        'invite_status' => 'invited',
                        'payment_status' => 'pending',
                    ]);

                    if ($member->player?->user_id) {
                        SendNotificationJob::dispatch(
                            $member->player->user_id,
                            'lobby_invite',
                            ['lobby_id' => $lobby->id]
                        );
                    }
                }
            }

            return $lobby;
        });

        // 6. Correction V: compute slot_warning after insert using overlap logic
        $competingCount = DB::table('match_lobbies')
            ->where('venue_id', $lobby->venue_id)
            ->where('match_date', $lobby->match_date)
            ->whereIn('status', ['forming', 'payment_pending'])
            ->where('id', '!=', $lobby->id)
            ->where('start_time', '<', $lobby->end_time)
            ->where('end_time', '>', $lobby->start_time)
            ->count();

        $slotWarning = $competingCount > 0;

        return response()->json([
            'lobby' => $lobby->fresh()->load(['venue', 'sport', 'participants.player.user:id,name']),
            'invite_code' => $lobby->invite_code,
            'slot_warning' => $slotWarning,
        ], 201);
    }

    /**
     * Show match lobby details with slot_warning (Correction V).
     */
    public function show(string $id): JsonResponse
    {
        $lobby = MatchLobby::with([
            'venue',
            'sport',
            'creator.user:id,name',
            'participants.player.user:id,name',
        ])->findOrFail($id);

        $competingCount = DB::table('match_lobbies')
            ->where('venue_id', $lobby->venue_id)
            ->where('match_date', $lobby->match_date)
            ->whereIn('status', ['forming', 'payment_pending'])
            ->where('id', '!=', $lobby->id)
            ->where('start_time', '<', $lobby->end_time)
            ->where('end_time', '>', $lobby->start_time)
            ->count();

        return response()->json([
            'lobby' => $lobby,
            'slot_warning' => $competingCount > 0,
        ], 200);
    }

    /**
     * List public and mixed forming match lobbies.
     */
    public function publicLobbies(Request $request): JsonResponse
    {
        $query = MatchLobby::whereIn('status', ['forming', 'payment_pending'])
            ->whereIn('match_type', ['public', 'mixed'])
            ->where('match_date', '>=', Carbon::today()->toDateString())
            ->with(['venue', 'sport', 'creator.user:id,name']);

        if ($request->filled('sport_id')) {
            $query->where('sport_id', $request->sport_id);
        }

        if ($request->filled('date')) {
            $query->where('match_date', $request->date);
        }

        if ($request->filled('city')) {
            $query->whereHas('venue', function ($q) use ($request) {
                $q->where('city', $request->city);
            });
        }

        $lobbies = $query->orderBy('match_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->paginate(15);

        return response()->json(['lobbies' => $lobbies], 200);
    }

    /**
     * Preview lobby by invite code with countdown and slot_warning.
     */
    public function previewByInviteCode(string $inviteCode): JsonResponse
    {
        $lobby = MatchLobby::where('invite_code', $inviteCode)
            ->with(['venue', 'sport', 'creator.user:id,name', 'participants.player.user:id,name'])
            ->first();

        if (!$lobby) {
            return response()->json(['message' => 'كود الدعوة غير صالح'], 404);
        }

        $competingCount = DB::table('match_lobbies')
            ->where('venue_id', $lobby->venue_id)
            ->where('match_date', $lobby->match_date)
            ->whereIn('status', ['forming', 'payment_pending'])
            ->where('id', '!=', $lobby->id)
            ->where('start_time', '<', $lobby->end_time)
            ->where('end_time', '>', $lobby->start_time)
            ->count();

        return response()->json([
            'lobby' => $lobby,
            'expires_at' => $lobby->expires_at,
            'slot_warning' => $competingCount > 0,
        ], 200);
    }

    /**
     * Join match lobby.
     */
    public function join(string $id, Request $request): JsonResponse
    {
        $profile = $request->user()->playerProfile;
        if (!$profile) {
            return response()->json(['message' => 'ملف اللاعب غير موجود'], 404);
        }

        $lobby = MatchLobby::findOrFail($id);

        if (!in_array($lobby->status, ['forming', 'payment_pending'])) {
            return response()->json(['message' => 'المباراة مغلقة ولم تعد تقبل لاعبين'], 422);
        }

        if ($lobby->expires_at && Carbon::parse($lobby->expires_at)->isPast()) {
            return response()->json(['message' => 'انتهت صلاحية غرفة المباراة'], 422);
        }

        // Count current accepted participants
        $acceptedCount = $lobby->participants()->where('invite_status', 'accepted')->count();
        if ($acceptedCount >= $lobby->required_players) {
            return response()->json(['message' => 'اكتمل عدد اللاعبين في المباراة'], 422);
        }

        $existing = MatchParticipant::where('lobby_id', $lobby->id)
            ->where('player_id', $profile->id)
            ->first();

        if ($existing && $existing->invite_status === 'accepted') {
            return response()->json(['message' => 'أنت منضم بالفعل إلى هذه المباراة'], 422);
        }

        // Determine invite status based on match_type
        $newStatus = match ($lobby->match_type) {
            'public' => 'accepted',
            'mixed' => 'pending_approval',
            default => 'accepted',
        };

        $participant = MatchParticipant::updateOrCreate(
            ['lobby_id' => $lobby->id, 'player_id' => $profile->id],
            ['invite_status' => $newStatus, 'payment_status' => 'pending']
        );

        if ($newStatus === 'accepted') {
            event(new ParticipantJoined($participant));

            // Check if full
            $updatedAcceptedCount = $lobby->participants()->where('invite_status', 'accepted')->count();
            if ($updatedAcceptedCount >= $lobby->required_players) {
                $creator = PlayerProfile::find($lobby->creator_id);
                if ($creator) {
                    SendNotificationJob::dispatch($creator->user_id, 'match_full', ['lobby_id' => $lobby->id]);
                }
            }
        }

        return response()->json([
            'participant' => $participant->load('player.user:id,name'),
            'message' => $newStatus === 'accepted' ? 'تم الانضمام بنجاح' : 'تم إرسال طلب الانضمام في انتظار موافقة المنشئ',
        ], 200);
    }

    /**
     * Creator invites player by username.
     */
    public function invite(string $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string'],
        ]);

        $profile = $request->user()->playerProfile;
        if (!$profile) {
            return response()->json(['message' => 'ملف اللاعب غير موجود'], 404);
        }

        $lobby = MatchLobby::findOrFail($id);
        if ($lobby->creator_id !== $profile->id) {
            return response()->json(['message' => 'منشئ المباراة فقط يمكنه إرسال الدعوات'], 403);
        }

        $targetProfile = PlayerProfile::where('username', $validated['username'])->first();
        if (!$targetProfile) {
            return response()->json(['message' => 'اللاعب غير موجود'], 404);
        }

        $participant = MatchParticipant::firstOrCreate(
            ['lobby_id' => $lobby->id, 'player_id' => $targetProfile->id],
            ['invite_status' => 'invited', 'payment_status' => 'pending']
        );

        SendNotificationJob::dispatch(
            $targetProfile->user_id,
            'lobby_invite',
            ['lobby_id' => $lobby->id]
        );

        return response()->json([
            'participant' => $participant->load('player.user:id,name'),
            'message' => 'تمت دعوة اللاعب بنجاح',
        ], 201);
    }

    /**
     * Player accepts or declines their invite.
     */
    public function respondInvite(string $id, string $participantId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:accepted,declined'],
        ]);

        $profile = $request->user()->playerProfile;
        if (!$profile) {
            return response()->json(['message' => 'ملف اللاعب غير موجود'], 404);
        }

        $participant = MatchParticipant::where('id', $participantId)
            ->where('lobby_id', $id)
            ->where('player_id', $profile->id)
            ->firstOrFail();

        $lobby = MatchLobby::findOrFail($id);

        if ($validated['status'] === 'accepted') {
            $acceptedCount = $lobby->participants()->where('invite_status', 'accepted')->count();
            if ($acceptedCount >= $lobby->required_players) {
                return response()->json(['message' => 'اكتمل عدد اللاعبين في المباراة'], 422);
            }

            $participant->update(['invite_status' => 'accepted']);
            event(new ParticipantJoined($participant));
        } else {
            $participant->update(['invite_status' => 'declined']);
            event(new ParticipantLeft($lobby->id, $participant->id));
        }

        return response()->json(['participant' => $participant->fresh()], 200);
    }

    /**
     * Creator approves or rejects participant's pending request.
     */
    public function approveParticipant(string $id, string $participantId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:approve,reject'],
        ]);

        $profile = $request->user()->playerProfile;
        if (!$profile) {
            return response()->json(['message' => 'ملف اللاعب غير موجود'], 404);
        }

        $lobby = MatchLobby::findOrFail($id);
        if ($lobby->creator_id !== $profile->id) {
            return response()->json(['message' => 'منشئ المباراة فقط يمكنه قبول أو رفض الطلبات'], 403);
        }

        $participant = MatchParticipant::where('id', $participantId)
            ->where('lobby_id', $id)
            ->firstOrFail();

        if ($validated['action'] === 'approve') {
            $acceptedCount = $lobby->participants()->where('invite_status', 'accepted')->count();
            if ($acceptedCount >= $lobby->required_players) {
                return response()->json(['message' => 'اكتمل عدد اللاعبين في المباراة'], 422);
            }

            $participant->update(['invite_status' => 'accepted']);
            event(new ParticipantJoined($participant));
        } else {
            $participant->update(['invite_status' => 'declined']);
            event(new ParticipantLeft($lobby->id, $participant->id));
        }

        return response()->json(['participant' => $participant->fresh()], 200);
    }

    /**
     * Creator cancels match lobby.
     */
    public function destroy(string $id, Request $request): JsonResponse
    {
        $profile = $request->user()->playerProfile;
        if (!$profile) {
            return response()->json(['message' => 'ملف اللاعب غير موجود'], 404);
        }

        $lobby = MatchLobby::findOrFail($id);
        if ($lobby->creator_id !== $profile->id) {
            return response()->json(['message' => 'منشئ المباراة فقط يمكنه إلغاء المباراة'], 403);
        }

        DB::transaction(function () use ($lobby) {
            $lobby->update([
                'status' => 'cancelled',
                'cancellation_reason' => 'creator_cancelled',
                'cancelled_at' => now(),
            ]);

            // Dispatch refund for any completed payments
            $paidPayments = Payment::where('lobby_id', $lobby->id)
                ->where('status', 'completed')
                ->get();

            foreach ($paidPayments as $payment) {
                ProcessRefundJob::dispatch($payment->id);
            }

            event(new LobbyStatusChanged($lobby->fresh()));
        });

        return response()->json(['message' => 'تم إلغاء المباراة بنجاح'], 200);
    }
}

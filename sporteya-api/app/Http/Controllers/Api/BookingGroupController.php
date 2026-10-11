<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendNotificationJob;
use App\Models\BookingGroup;
use App\Models\BookingGroupMember;
use App\Models\PlayerProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingGroupController extends Controller
{
    /**
     * List booking groups where authenticated player is creator or member.
     */
    public function index(Request $request): JsonResponse
    {
        $profile = $request->user()->playerProfile;

        if (!$profile) {
            return response()->json(['message' => 'ملف اللاعب غير موجود'], 404);
        }

        $groups = BookingGroup::where('creator_id', $profile->id)
            ->orWhereHas('members', function ($q) use ($profile) {
                $q->where('player_id', $profile->id);
            })
            ->with(['sport', 'members.player.user:id,name'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['booking_groups' => $groups], 200);
    }

    /**
     * Create a new booking group.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'sport_id' => ['required', 'uuid', 'exists:sports,id'],
        ]);

        $profile = $request->user()->playerProfile;

        if (!$profile) {
            return response()->json(['message' => 'ملف اللاعب غير موجود'], 404);
        }

        $group = DB::transaction(function () use ($profile, $validated) {
            $inviteCode = Str::upper(Str::random(8));
            while (BookingGroup::where('invite_code', $inviteCode)->exists()) {
                $inviteCode = Str::upper(Str::random(8));
            }

            $group = BookingGroup::create([
                'creator_id' => $profile->id,
                'sport_id' => $validated['sport_id'],
                'name' => $validated['name'],
                'invite_code' => $inviteCode,
            ]);

            BookingGroupMember::create([
                'booking_group_id' => $group->id,
                'player_id' => $profile->id,
                'role' => 'admin',
            ]);

            return $group;
        });

        return response()->json(['booking_group' => $group->load(['sport', 'members.player.user:id,name'])], 201);
    }

    /**
     * Get booking group details.
     */
    public function show(string $id, Request $request): JsonResponse
    {
        $profile = $request->user()->playerProfile;

        if (!$profile) {
            return response()->json(['message' => 'ملف اللاعب غير موجود'], 404);
        }

        $group = BookingGroup::with(['sport', 'members.player.user:id,name'])->findOrFail($id);

        $isMember = $group->members->contains('player_id', $profile->id);
        if (!$isMember && $group->creator_id !== $profile->id) {
            return response()->json(['message' => 'غير مصرح لك بعرض هذه المجموعة'], 403);
        }

        return response()->json(['booking_group' => $group], 200);
    }

    /**
     * Delete booking group (creator only).
     */
    public function destroy(string $id, Request $request): JsonResponse
    {
        $profile = $request->user()->playerProfile;

        if (!$profile) {
            return response()->json(['message' => 'ملف اللاعب غير موجود'], 404);
        }

        $group = BookingGroup::findOrFail($id);

        if ($group->creator_id !== $profile->id) {
            return response()->json(['message' => 'منشئ المجموعة فقط يمكنه حذفها'], 403);
        }

        $group->delete();

        return response()->json(['message' => 'تم حذف المجموعة بنجاح'], 200);
    }

    /**
     * Add member by username and dispatch notification.
     */
    public function addMember(string $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string'],
        ]);

        $profile = $request->user()->playerProfile;

        if (!$profile) {
            return response()->json(['message' => 'ملف اللاعب غير موجود'], 404);
        }

        $group = BookingGroup::findOrFail($id);

        // Check permission (creator or admin member)
        $isAdmin = $group->creator_id === $profile->id ||
            $group->members()->where('player_id', $profile->id)->where('role', 'admin')->exists();

        if (!$isAdmin) {
            return response()->json(['message' => 'غير مصرح لك بإضافة أعضاء لهذه المجموعة'], 403);
        }

        $targetProfile = PlayerProfile::where('username', $validated['username'])->first();

        if (!$targetProfile) {
            return response()->json(['message' => 'اللاعب غير موجود'], 404);
        }

        // Check if already a member
        if ($group->members()->where('player_id', $targetProfile->id)->exists()) {
            return response()->json(['message' => 'اللاعب عضو بالفعل في هذه المجموعة'], 422);
        }

        $member = BookingGroupMember::create([
            'booking_group_id' => $group->id,
            'player_id' => $targetProfile->id,
            'role' => 'member',
        ]);

        // Dispatch group_invite notification to invited user
        SendNotificationJob::dispatch(
            $targetProfile->user_id,
            'group_invite',
            ['booking_group_id' => $group->id, 'group_name' => $group->name],
            'دعوة للانضمام لمجموعة',
            "تمت دعوتك للانضمام إلى مجموعة {$group->name}."
        );

        return response()->json([
            'member' => $member->load('player.user:id,name'),
            'message' => 'تمت إضافة العضو بنجاح',
        ], 201);
    }

    /**
     * Remove member from group.
     */
    public function removeMember(string $id, string $playerId, Request $request): JsonResponse
    {
        $profile = $request->user()->playerProfile;

        if (!$profile) {
            return response()->json(['message' => 'ملف اللاعب غير موجود'], 404);
        }

        $group = BookingGroup::findOrFail($id);

        $isSelf = $profile->id === $playerId;
        $isCreator = $group->creator_id === $profile->id;

        if (!$isSelf && !$isCreator) {
            return response()->json(['message' => 'غير مصرح لك بإزالة هذا العضو'], 403);
        }

        // Prevent creator from leaving without deleting group
        if ($group->creator_id === $playerId) {
            return response()->json(['message' => 'لا يمكن لمالك المجموعة مغادرتها، يجب حذف المجموعة'], 422);
        }

        $member = BookingGroupMember::where('booking_group_id', $group->id)
            ->where('player_id', $playerId)
            ->first();

        if (!$member) {
            return response()->json(['message' => 'العضو غير موجود في المجموعة'], 404);
        }

        $member->delete();

        return response()->json(['message' => 'تمت إزالة العضو بنجاح'], 200);
    }
}

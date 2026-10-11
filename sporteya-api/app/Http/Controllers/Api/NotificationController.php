<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * List user's notifications with unread count.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $unreadCount = Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->count();

        $notifications = Notification::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'unread_count' => $unreadCount,
            'notifications' => $notifications,
        ], 200);
    }

    /**
     * Mark single notification as read.
     */
    public function markAsRead(string $id, Request $request): JsonResponse
    {
        $notification = Notification::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $notification->update(['read_at' => now()]);

        return response()->json(['notification' => $notification], 200);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        Notification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'تم تحديد جميع الإشعارات كمقروءة'], 200);
    }
}

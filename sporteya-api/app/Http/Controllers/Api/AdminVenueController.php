<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendNotificationJob;
use App\Models\Venue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AdminVenueController extends Controller
{
    /**
     * Verify or activate a venue (Correction 2D).
     */
    public function verify(string $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'is_verified' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
        ]);

        $venue = Venue::findOrFail($id);

        $venue->update([
            'is_verified' => $validated['is_verified'],
            'is_active' => $validated['is_active'],
        ]);

        // Invalidate cached venue details
        Cache::forget("venue:{$id}");

        // If verified, send notification to venue owner
        if ($validated['is_verified']) {
            SendNotificationJob::dispatch(
                $venue->owner_id,
                'venue_verified',
                ['venue_id' => $venue->id, 'venue_name' => $venue->name],
                'تم تفعيل ملعبك',
                'تم التحقق من ملعبك! يمكنك الآن استقبال الحجوزات.'
            );
        }

        return response()->json(['venue' => $venue], 200);
    }
}

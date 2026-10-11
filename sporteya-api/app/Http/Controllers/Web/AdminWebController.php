<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Jobs\SendNotificationJob;
use App\Models\Venue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AdminWebController extends Controller
{
    public function venues(Request $request)
    {
        $status = $request->query('status');

        $query = Venue::with(['owner', 'sports.sport']);

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'pending') {
            $query->where('is_verified', false);
        }

        $venues = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('admin.venues.index', compact('venues', 'status'));
    }

    public function venueDetail($id)
    {
        $venue = Venue::with([
            'owner',
            'sports.sport',
            'media',
            'schedules',
            'pricing.sport',
            'cancellationPolicy',
        ])->findOrFail($id);

        return view('admin.venues.show', compact('venue'));
    }

    public function verifyVenue($id, Request $request)
    {
        $validated = $request->validate([
            'action' => ['required', 'in:approve,reject,toggle_active'],
        ]);

        $venue = Venue::findOrFail($id);

        if ($validated['action'] === 'approve') {
            $venue->update([
                'is_verified' => true,
                'is_active' => true,
                'verified_at' => now(),
            ]);

            SendNotificationJob::dispatch(
                $venue->owner_id,
                'venue_verified',
                ['venue_id' => $venue->id, 'venue_name' => $venue->name],
                'تم تفعيل ملعبك',
                'تم التحقق من ملعبك! يمكنك الآن استقبال الحجوزات.'
            );

            $message = 'تم التحقق من الملعب وتفعيله بنجاح.';
        } elseif ($validated['action'] === 'reject') {
            $venue->update([
                'is_verified' => false,
                'is_active' => false,
            ]);
            $message = 'تم رفض الملعب وإيقاف نشاطه.';
        } else {
            $venue->update([
                'is_active' => !$venue->is_active,
            ]);
            $message = $venue->is_active ? 'تم تنشيط الملعب.' : 'تم إيقاف الملعب مؤقتاً.';
        }

        Cache::forget("venue:{$venue->id}");

        return back()->with('success', $message);
    }
}

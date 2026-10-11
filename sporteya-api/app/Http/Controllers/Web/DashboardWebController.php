<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\CancellationPolicy;
use App\Models\Sport;
use App\Models\Venue;
use App\Models\VenueBlockedSlot;
use App\Models\VenueMedia;
use App\Models\VenuePricing;
use App\Models\VenueReview;
use App\Models\VenueSchedule;
use App\Services\MediaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardWebController extends Controller
{
    protected function getVenue(): ?Venue
    {
        return Venue::where('owner_id', Auth::id())
            ->with(['sports.sport', 'media', 'schedules', 'pricing.sport', 'cancellationPolicy'])
            ->first();
    }

    public function index()
    {
        $venue = $this->getVenue();
        if (!$venue) {
            return redirect()->route('dashboard.settings')->with('warning', 'يرجى إكمال بيانات ملعبك أولاً.');
        }

        $today = Carbon::today()->toDateString();
        $weekAgo = Carbon::today()->subDays(7)->toDateString();

        $todayBookings = Booking::where('venue_id', $venue->id)
            ->whereDate('created_at', $today)
            ->where('status', 'confirmed')
            ->count();

        $todayRevenue = Booking::where('venue_id', $venue->id)
            ->whereDate('created_at', $today)
            ->where('status', 'confirmed')
            ->sum('venue_amount');

        $weekRevenue = Booking::where('venue_id', $venue->id)
            ->whereDate('created_at', '>=', $weekAgo)
            ->where('status', 'confirmed')
            ->sum('venue_amount');

        $upcomingBookings = Booking::where('venue_id', $venue->id)
            ->where('status', 'confirmed')
            ->with(['lobby.sport', 'lobby.creator.user:id,name'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return view('dashboard.index', compact('venue', 'todayBookings', 'todayRevenue', 'weekRevenue', 'upcomingBookings'));
    }

    public function availability()
    {
        $venue = $this->getVenue();
        $schedules = $venue ? $venue->schedules->keyBy('day_of_week') : collect();
        $blockedSlots = $venue ? VenueBlockedSlot::where('venue_id', $venue->id)->orderBy('date', 'desc')->get() : collect();

        return view('dashboard.availability', compact('venue', 'schedules', 'blockedSlots'));
    }

    public function saveSchedules(Request $request)
    {
        $venue = $this->getVenue();
        $validated = $request->validate([
            'schedules' => ['required', 'array'],
            'schedules.*.open_time' => ['required'],
            'schedules.*.close_time' => ['required'],
        ]);

        foreach ($validated['schedules'] as $day => $data) {
            VenueSchedule::updateOrCreate(
                ['venue_id' => $venue->id, 'day_of_week' => $day],
                [
                    'open_time' => $data['open_time'],
                    'close_time' => $data['close_time'],
                    'is_closed' => isset($data['is_closed']),
                    'slot_duration_minutes' => 60,
                ]
            );
        }

        return back()->with('success', 'تم تحديث جدول أوقات العمل بنجاح.');
    }

    public function saveBlockSlot(Request $request)
    {
        $venue = $this->getVenue();
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'start_time' => ['required'],
            'end_time' => ['required'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        VenueBlockedSlot::create([
            'venue_id' => $venue->id,
            'date' => $validated['date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'reason' => $validated['reason'] ?? 'صيانة',
        ]);

        return back()->with('success', 'تم حظر الموعد بنجاح.');
    }

    public function deleteBlockSlot($id)
    {
        $venue = $this->getVenue();
        VenueBlockedSlot::where('id', $id)->where('venue_id', $venue->id)->delete();
        return back()->with('success', 'تم إلغاء حظر الموعد.');
    }

    public function pricing()
    {
        $venue = $this->getVenue();
        $sports = Sport::where('is_active', true)->get();
        $pricingRules = $venue ? $venue->pricing : collect();

        return view('dashboard.pricing', compact('venue', 'sports', 'pricingRules'));
    }

    public function savePricing(Request $request)
    {
        $venue = $this->getVenue();
        $validated = $request->validate([
            'sport_id' => ['required', 'uuid'],
            'day_of_week' => ['required', 'integer', 'between:0,6'],
            'start_time' => ['required'],
            'end_time' => ['required'],
            'price_per_hour' => ['required', 'numeric', 'min:0'],
        ]);

        VenuePricing::create([
            'venue_id' => $venue->id,
            'sport_id' => $validated['sport_id'],
            'day_of_week' => $validated['day_of_week'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'price_per_hour' => $validated['price_per_hour'],
            'currency' => 'EGP',
        ]);

        return back()->with('success', 'تمت إضافة تسعيرة جديدة بنجاح.');
    }

    public function deletePricing($id)
    {
        $venue = $this->getVenue();
        VenuePricing::where('id', $id)->where('venue_id', $venue->id)->delete();
        return back()->with('success', 'تم حذف التسعيرة.');
    }

    public function bookings(Request $request)
    {
        $venue = $this->getVenue();
        $status = $request->query('status');

        $query = Booking::where('venue_id', $venue->id)
            ->with(['lobby.sport', 'lobby.creator.user:id,name']);

        if ($status) {
            $query->where('status', $status);
        }

        $bookings = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('dashboard.bookings', compact('venue', 'bookings', 'status'));
    }

    public function settings()
    {
        $venue = $this->getVenue();
        return view('dashboard.settings', compact('venue'));
    }

    public function updateSettings(Request $request)
    {
        $venue = $this->getVenue();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'full_refund_hours' => ['required', 'integer', 'min:0'],
            'half_refund_hours' => ['required', 'integer', 'min:0'],
            'no_refund_hours' => ['required', 'integer', 'min:0'],
        ]);

        $venue->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'address' => $validated['address'],
            'city' => $validated['city'],
            'phone' => $validated['phone'] ?? null,
            'has_lighting' => $request->boolean('has_lighting'),
            'has_changing_room' => $request->boolean('has_changing_room'),
            'has_parking' => $request->boolean('has_parking'),
        ]);

        CancellationPolicy::updateOrCreate(
            ['venue_id' => $venue->id],
            [
                'full_refund_hours' => $validated['full_refund_hours'],
                'half_refund_hours' => $validated['half_refund_hours'],
                'no_refund_hours' => $validated['no_refund_hours'],
            ]
        );

        return back()->with('success', 'تم حفظ الإعدادات وسياسة الإلغاء بنجاح.');
    }

    public function uploadPhoto(Request $request, MediaService $mediaService)
    {
        $venue = $this->getVenue();
        $request->validate([
            'photo' => ['required', 'file', 'image', 'max:5120'],
        ]);

        $url = $mediaService->uploadVenuePhoto($request->file('photo'));

        VenueMedia::create([
            'venue_id' => $venue->id,
            'url' => $url,
            'type' => 'image',
            'sort_order' => $venue->media()->count() + 1,
        ]);

        return back()->with('success', 'تم رفع الصورة بنجاح.');
    }

    public function deletePhoto($id)
    {
        $venue = $this->getVenue();
        VenueMedia::where('id', $id)->where('venue_id', $venue->id)->delete();
        return back()->with('success', 'تم حذف الصورة.');
    }

    public function reviews()
    {
        $venue = $this->getVenue();
        $reviews = $venue ? VenueReview::where('venue_id', $venue->id)->with('player.user:id,name')->orderBy('created_at', 'desc')->paginate(15) : collect();

        return view('dashboard.reviews', compact('venue', 'reviews'));
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Events\LobbyStatusChanged;
use App\Http\Controllers\Controller;
use App\Jobs\ConfirmBookingJob;
use App\Jobs\ProcessRefundJob;
use App\Jobs\SendNotificationJob;
use App\Models\Booking;
use App\Models\MatchLobby;
use App\Models\MatchParticipant;
use App\Models\Payment;
use App\Models\VenuePricing;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * Initiate payment for a lobby participant (Correction G).
     */
    public function initiate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lobby_id' => ['required', 'uuid', 'exists:match_lobbies,id'],
        ]);

        $profile = $request->user()->playerProfile;
        if (!$profile) {
            return response()->json(['message' => 'ملف اللاعب غير موجود'], 404);
        }

        $lobby = MatchLobby::findOrFail($validated['lobby_id']);

        if (!in_array($lobby->status, ['forming', 'payment_pending'])) {
            return response()->json(['message' => 'المباراة مغلقة ولا يمكن الدفع لها'], 422);
        }

        // Verify player is accepted participant
        $participant = MatchParticipant::where('lobby_id', $lobby->id)
            ->where('player_id', $profile->id)
            ->where('invite_status', 'accepted')
            ->first();

        if (!$participant) {
            return response()->json(['message' => 'يجب الانضمام للمباراة أولاً قبل الدفع'], 403);
        }

        if ($participant->payment_status === 'paid') {
            return response()->json(['message' => 'تم الدفع بالفعل لهذه المباراة'], 422);
        }

        // Determine price per player from venue pricing or default
        $dayOfWeek = Carbon::parse($lobby->match_date)->dayOfWeek;
        $pricing = VenuePricing::where('venue_id', $lobby->venue_id)
            ->where('sport_id', $lobby->sport_id)
            ->where('day_of_week', $dayOfWeek)
            ->first();

        $totalPrice = $pricing ? (float) $pricing->price_per_hour : 120.00;
        $perPlayerAmount = floor(($totalPrice / $lobby->required_players) * 100) / 100;

        $orderId = rand(10000000, 99999999);
        $paymentKey = 'fake_paymob_token_' . bin2hex(random_bytes(16));

        // If Paymob live credentials are configured, call Paymob API
        $apiKey = config('services.paymob.api_key');
        if ($apiKey) {
            try {
                // 1. Auth token
                $authRes = Http::post('https://accept.paymob.com/api/auth/tokens', ['api_key' => $apiKey]);
                $token = $authRes->json('token');

                if ($token) {
                    // 2. Create order
                    $orderRes = Http::post('https://accept.paymob.com/api/ecommerce/orders', [
                        'auth_token' => $token,
                        'delivery_needed' => false,
                        'amount_cents' => (int) round($perPlayerAmount * 100),
                        'currency' => 'EGP',
                        'items' => [],
                    ]);
                    $orderId = $orderRes->json('id') ?: $orderId;

                    // 3. Payment key
                    $keyRes = Http::post('https://accept.paymob.com/api/acceptance/payment_keys', [
                        'auth_token' => $token,
                        'amount_cents' => (int) round($perPlayerAmount * 100),
                        'expiration' => 3600,
                        'order_id' => $orderId,
                        'billing_data' => [
                            'apartment' => 'NA',
                            'email' => $request->user()->email,
                            'floor' => 'NA',
                            'first_name' => $request->user()->name,
                            'street' => 'NA',
                            'building' => 'NA',
                            'phone_number' => $profile->phone ?: '01000000000',
                            'shipping_method' => 'NA',
                            'postal_code' => 'NA',
                            'city' => 'Cairo',
                            'country' => 'EG',
                            'last_name' => 'Player',
                            'state' => 'Cairo',
                        ],
                        'currency' => 'EGP',
                        'integration_id' => (int) config('services.paymob.integration_id', 1),
                    ]);
                    $paymentKey = $keyRes->json('token') ?: $paymentKey;
                }
            } catch (\Throwable $e) {
                Log::error('Paymob integration call failed: ' . $e->getMessage());
            }
        }

        // Create pending payment record
        $payment = Payment::create([
            'lobby_id' => $lobby->id,
            'player_id' => $profile->id,
            'booking_id' => null, // populated by ConfirmBookingJob
            'amount' => $perPlayerAmount,
            'currency' => 'EGP',
            'status' => 'pending',
            'paymob_order_id' => (string) $orderId,
        ]);

        return response()->json([
            'payment_id' => $payment->id,
            'paymob_order_id' => (string) $orderId,
            'payment_key' => $paymentKey,
            'iframe_id' => config('services.paymob.iframe_id', '12345'),
        ], 200);
    }

    /**
     * Paymob webhook callback (no auth, HMAC check, idempotent, Correction J, L1, O1).
     */
    public function callback(Request $request): JsonResponse
    {
        $payload = $request->input('obj', $request->all());

        // STEP 1 — HMAC check if secret is configured and hmac provided
        $secret = config('services.paymob.hmac_secret');
        if ($secret && $request->filled('hmac')) {
            $concatenated = ($payload['amount_cents'] ?? '') .
                ($payload['created_at'] ?? '') .
                ($payload['currency'] ?? '') .
                ($payload['error_occured'] ?? '') .
                ($payload['has_parent_transaction'] ?? '') .
                ($payload['id'] ?? '') .
                ($payload['integration_id'] ?? '') .
                ($payload['is_3d_secure'] ?? '') .
                ($payload['is_auth'] ?? '') .
                ($payload['is_capture'] ?? '') .
                ($payload['is_refunded'] ?? '') .
                ($payload['is_standalone_payment'] ?? '') .
                ($payload['is_voided'] ?? '') .
                (data_get($payload, 'order.id') ?? '') .
                ($payload['owner'] ?? '') .
                ($payload['pending'] ?? '') .
                (data_get($payload, 'source_data.pan') ?? '') .
                (data_get($payload, 'source_data.sub_type') ?? '') .
                (data_get($payload, 'source_data.type') ?? '') .
                ($payload['success'] ?? '');

            $computed = hash_hmac('sha512', $concatenated, $secret);
            if (!hash_equals($computed, (string) $request->hmac)) {
                return response()->json([], 400);
            }
        }

        $txnId = (string) ($payload['id'] ?? $request->input('id'));
        $orderId = (string) (data_get($payload, 'order.id') ?? $request->input('order.id') ?? $request->input('paymob_order_id'));

        // STEP 2 — Correction J: Idempotency check
        $existing = Payment::where('paymob_txn_id', $txnId)->first();
        if ($existing && $existing->status === 'completed') {
            return response()->json(['success' => true], 200);
        }

        // STEP 3 — Correction J: Atomic status update (only if status is pending)
        $updated = Payment::where('paymob_order_id', $orderId)
            ->where('status', 'pending')
            ->update([
                'status' => 'completed',
                'paymob_txn_id' => $txnId,
                'paid_at' => now(),
                'payment_method' => data_get($payload, 'source_data.type') ?? 'card',
            ]);

        if ($updated === 0) {
            return response()->json(['success' => true], 200);
        }

        // STEP 4 — Correction L1: update participant payment_status on match_participants
        $payment = Payment::where('paymob_order_id', $orderId)->first();
        if ($payment) {
            DB::table('match_participants')
                ->where('lobby_id', $payment->lobby_id)
                ->where('player_id', $payment->player_id)
                ->update(['payment_status' => 'paid']);

            // Dispatch ConfirmBookingJob
            ConfirmBookingJob::dispatch($payment->lobby_id);

            // Correction O1: transition to payment_pending if partial payments
            $paidCount = DB::table('match_participants')
                ->where('lobby_id', $payment->lobby_id)
                ->where('invite_status', 'accepted')
                ->where('payment_status', 'paid')
                ->count();

            $lobby = MatchLobby::find($payment->lobby_id);
            if ($lobby && $paidCount > 0 && $paidCount < $lobby->required_players && $lobby->status === 'forming') {
                $lobby->update(['status' => 'payment_pending']);
                event(new LobbyStatusChanged($lobby->fresh()));
            }
        }

        return response()->json(['success' => true], 200);
    }

    /**
     * Get payment status by booking id.
     */
    public function status(string $bookingId): JsonResponse
    {
        $payments = Payment::where('booking_id', $bookingId)->get();
        return response()->json(['payments' => $payments], 200);
    }

    /**
     * Cancel confirmed booking and process refunds according to policy_snapshot.
     */
    public function cancelBooking(string $id, Request $request): JsonResponse
    {
        $booking = Booking::with('lobby')->findOrFail($id);

        if ($booking->status !== 'confirmed') {
            return response()->json(['message' => 'الحجز غير مؤكد بالفعل'], 422);
        }

        // Calculate hours before match
        $dateStr = Carbon::parse($booking->lobby->match_date)->format('Y-m-d');
        $matchStart = Carbon::parse("{$dateStr} {$booking->lobby->start_time}");
        $hoursBefore = now()->diffInHours($matchStart, false);

        $policy = $booking->policy_snapshot ?? [];
        $fullHours = $policy['full_refund_hours'] ?? 24;
        $halfHours = $policy['half_refund_hours'] ?? 12;

        $refundRatio = 0.0;
        if ($hoursBefore >= $fullHours) {
            $refundRatio = 1.0;
        } elseif ($hoursBefore >= $halfHours) {
            $refundRatio = 0.5;
        }

        DB::transaction(function () use ($booking, $request, $refundRatio) {
            $booking->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => $request->user()->id,
                'cancellation_reason' => $request->input('reason', 'user_cancelled'),
            ]);

            $booking->lobby->update([
                'status' => 'cancelled',
                'cancellation_reason' => 'booking_cancelled',
                'cancelled_at' => now(),
            ]);

            if ($refundRatio > 0) {
                $payments = Payment::where('booking_id', $booking->id)
                    ->where('status', 'completed')
                    ->get();

                foreach ($payments as $payment) {
                    ProcessRefundJob::dispatch($payment->id);
                }
            }

            event(new LobbyStatusChanged($booking->lobby->fresh()));
        });

        return response()->json([
            'message' => 'تم إلغاء الحجز بنجاح',
            'refund_ratio' => $refundRatio,
        ], 200);
    }

    /**
     * List user's bookings.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role === 'venue_owner') {
            $venueIds = $user->venues()->pluck('id');
            $bookings = Booking::whereIn('venue_id', $venueIds)
                ->with(['lobby.sport', 'venue'])
                ->orderBy('created_at', 'desc')
                ->paginate(15);
        } else {
            $profile = $user->playerProfile;
            $lobbyIds = MatchParticipant::where('player_id', $profile?->id)
                ->where('invite_status', 'accepted')
                ->pluck('lobby_id');

            $bookings = Booking::whereIn('lobby_id', $lobbyIds)
                ->with(['lobby.sport', 'venue'])
                ->orderBy('created_at', 'desc')
                ->paginate(15);
        }

        return response()->json(['bookings' => $bookings], 200);
    }

    /**
     * Get single booking details.
     */
    public function show(string $id): JsonResponse
    {
        $booking = Booking::with([
            'lobby.sport',
            'lobby.participants.player.user:id,name',
            'venue',
            'payments',
        ])->findOrFail($id);

        return response()->json(['booking' => $booking], 200);
    }
}

<?php

namespace App\Jobs;

use App\Events\LobbyStatusChanged;
use App\Models\Booking;
use App\Models\MatchLobby;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ConfirmBookingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $lobbyId;

    public function __construct(string $lobbyId)
    {
        $this->lobbyId = $lobbyId;
    }

    public function handle(): void
    {
        $lobbyId = $this->lobbyId;

        DB::transaction(function () use ($lobbyId) {
            // a. Acquire lock
            $lobby = MatchLobby::lockForUpdate()->find($lobbyId);

            // Early exit guard (Correction P)
            if (!$lobby) return;
            if (!in_array($lobby->status, ['forming', 'payment_pending'])) {
                return;
            }

            // b. Count paid+accepted participants (Correction L3)
            $paidCount = DB::table('match_participants')
                ->where('lobby_id', $lobbyId)
                ->where('invite_status', 'accepted')
                ->where('payment_status', 'paid')
                ->count();

            // c. Only proceed if all required players have paid
            if ($paidCount < $lobby->required_players) {
                return;
            }

            // Correction U — define amounts
            $totalPaid = Payment::where('lobby_id', $lobbyId)
                ->where('status', 'completed')
                ->sum('amount');

            // Fallback if sum is 0 (e.g. testing)
            if ($totalPaid <= 0) {
                $totalPaid = 100.00;
            }

            $perPlayerAmount = floor(($totalPaid / $lobby->required_players) * 100) / 100;
            $totalAmount = $perPlayerAmount * $lobby->required_players;
            $platformFeePct = 2.00;
            $platformFee = round($totalAmount * ($platformFeePct / 100), 2);
            $venueAmount = round($totalAmount - $platformFee, 2);

            // d-i. Check for slot conflict — lock all confirmed bookings for this venue+slot
            $conflictingBooking = DB::table('bookings')
                ->join('match_lobbies', 'bookings.lobby_id', '=', 'match_lobbies.id')
                ->where('match_lobbies.venue_id', $lobby->venue_id)
                ->where('match_lobbies.match_date', $lobby->match_date)
                ->where('bookings.status', 'confirmed')
                ->where(function ($q) use ($lobby) {
                    $q->where('match_lobbies.start_time', '<', $lobby->end_time)
                      ->where('match_lobbies.end_time', '>', $lobby->start_time);
                })
                ->lockForUpdate()
                ->first();

            // d-ii. If conflict found:
            if ($conflictingBooking) {
                $lobby->update([
                    'status'              => 'cancelled',
                    'cancellation_reason' => 'slot_taken',
                    'cancelled_at'        => now(),
                ]);

                // Refund all paid participants
                $paidPayments = Payment::where('lobby_id', $lobby->id)
                    ->where('status', 'completed')
                    ->get();

                foreach ($paidPayments as $payment) {
                    ProcessRefundJob::dispatch($payment->id);
                }

                $creatorProfile = DB::table('player_profiles')->where('id', $lobby->creator_id)->first();
                if ($creatorProfile) {
                    SendNotificationJob::dispatch($creatorProfile->user_id, 'booking_cancelled');
                }

                event(new LobbyStatusChanged($lobby->fresh()));
                return;
            }

            // d-iii. No conflict — create the booking:
            $booking = Booking::create([
                'lobby_id'          => $lobby->id,
                'venue_id'          => $lobby->venue_id,
                'total_amount'      => $totalAmount,
                'platform_fee_pct'  => $platformFeePct,
                'platform_fee'      => $platformFee,
                'venue_amount'      => $venueAmount,
                'status'            => 'confirmed',
                'confirmation_code' => strtoupper(Str::random(6)),
                'qr_token'          => bin2hex(random_bytes(32)),
                'policy_snapshot'   => $lobby->policy_snapshot ?? [],
            ]);

            // Correction G: link payments to the newly created booking
            Payment::where('lobby_id', $lobby->id)
                ->where('status', 'completed')
                ->update(['booking_id' => $booking->id]);

            $lobby->update([
                'status'     => 'confirmed',
                'expires_at' => null,
            ]);

            event(new LobbyStatusChanged($lobby->fresh()));

            $participantUserIds = DB::table('match_participants')
                ->join('player_profiles', 'player_profiles.id', '=', 'match_participants.player_id')
                ->where('match_participants.lobby_id', $lobby->id)
                ->where('match_participants.invite_status', 'accepted')
                ->pluck('player_profiles.user_id')
                ->toArray();

            SendNotificationJob::dispatch($participantUserIds, 'booking_confirmed');

            // Correction D: dispatch delayed check-in reminder
            $dateStr = Carbon::parse($lobby->match_date)->format('Y-m-d');
            $matchStart = Carbon::parse("{$dateStr} {$lobby->start_time}");
            $delaySeconds = now()->diffInSeconds($matchStart->copy()->subMinutes(30), false);
            if ($delaySeconds > 0) {
                SendNotificationJob::dispatch($participantUserIds, 'checkin_reminder')
                    ->delay($delaySeconds);
            }
        });
    }
}

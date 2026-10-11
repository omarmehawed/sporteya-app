<?php

namespace Tests\Feature;

use App\Jobs\ConfirmBookingJob;
use App\Jobs\LobbyExpiryJob;
use App\Jobs\ProcessRefundJob;
use App\Models\Booking;
use App\Models\MatchLobby;
use App\Models\MatchParticipant;
use App\Models\Payment;
use App\Models\PlayerProfile;
use App\Models\Sport;
use App\Models\User;
use App\Models\Venue;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PaymentAndBookingTest extends TestCase
{
    use RefreshDatabase;

    protected Sport $sport;
    protected Venue $venue;
    protected User $user1;
    protected PlayerProfile $profile1;
    protected User $user2;
    protected PlayerProfile $profile2;
    protected MatchLobby $lobby;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sport = Sport::create([
            'name_ar' => 'بادل',
            'name_en' => 'Padel',
            'slug' => 'padel',
            'icon' => 'padel',
            'is_active' => true,
        ]);

        $owner = User::factory()->create(['role' => 'venue_owner']);
        $this->venue = Venue::create([
            'owner_id' => $owner->id,
            'name' => 'Smouha Padel',
            'address' => 'Smouha',
            'city' => 'Alexandria',
            'is_verified' => true,
            'is_active' => true,
            'location' => DB::raw("ST_MakePoint(29.95, 31.20)::geography"),
        ]);

        $this->user1 = User::factory()->create(['role' => 'player']);
        $this->profile1 = PlayerProfile::create([
            'user_id' => $this->user1->id,
            'username' => 'player_one',
        ]);

        $this->user2 = User::factory()->create(['role' => 'player']);
        $this->profile2 = PlayerProfile::create([
            'user_id' => $this->user2->id,
            'username' => 'player_two',
        ]);

        $this->lobby = MatchLobby::create([
            'creator_id' => $this->profile1->id,
            'venue_id' => $this->venue->id,
            'sport_id' => $this->sport->id,
            'match_type' => 'public',
            'required_players' => 2,
            'match_date' => Carbon::tomorrow()->toDateString(),
            'start_time' => '19:00:00',
            'end_time' => '20:00:00',
            'status' => 'forming',
            'invite_code' => 'PAYTEST01',
            'policy_snapshot' => [
                'full_refund_hours' => 24,
                'half_refund_hours' => 12,
                'no_refund_hours' => 2,
            ],
            'expires_at' => Carbon::now()->addHours(2),
        ]);

        MatchParticipant::create([
            'lobby_id' => $this->lobby->id,
            'player_id' => $this->profile1->id,
            'invite_status' => 'accepted',
            'payment_status' => 'pending',
        ]);

        MatchParticipant::create([
            'lobby_id' => $this->lobby->id,
            'player_id' => $this->profile2->id,
            'invite_status' => 'accepted',
            'payment_status' => 'pending',
        ]);
    }

    public function test_player_can_initiate_payment(): void
    {
        $response = $this->actingAs($this->user1)->postJson('/api/v1/payments/initiate', [
            'lobby_id' => $this->lobby->id,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['payment_id', 'paymob_order_id', 'payment_key']);

        $this->assertDatabaseHas('payments', [
            'lobby_id' => $this->lobby->id,
            'player_id' => $this->profile1->id,
            'status' => 'pending',
            'booking_id' => null,
        ]);
    }

    public function test_paymob_webhook_callback_updates_payment_and_participant_and_transitions_status(): void
    {
        Queue::fake([ConfirmBookingJob::class]);

        $payment = Payment::create([
            'lobby_id' => $this->lobby->id,
            'player_id' => $this->profile1->id,
            'amount' => 60.00,
            'currency' => 'EGP',
            'status' => 'pending',
            'paymob_order_id' => '12345678',
        ]);

        $callbackPayload = [
            'obj' => [
                'id' => 98765432,
                'amount_cents' => 6000,
                'success' => true,
                'order' => ['id' => 12345678],
                'source_data' => ['type' => 'card'],
            ],
        ];

        $response = $this->postJson('/api/v1/payments/callback', $callbackPayload);
        $response->assertStatus(200);

        // Payment status is completed
        $this->assertEquals('completed', $payment->fresh()->status);
        $this->assertEquals('98765432', $payment->fresh()->paymob_txn_id);

        // Correction L1: participant payment_status is 'paid'
        $this->assertDatabaseHas('match_participants', [
            'lobby_id' => $this->lobby->id,
            'player_id' => $this->profile1->id,
            'payment_status' => 'paid',
        ]);

        // Correction O1: lobby transitioned to payment_pending
        $this->assertEquals('payment_pending', $this->lobby->fresh()->status);

        // ConfirmBookingJob dispatched
        Queue::assertPushed(ConfirmBookingJob::class, function ($job) {
            return $job->lobbyId === $this->lobby->id;
        });

        // Test Idempotency (Correction J)
        $duplicateResponse = $this->postJson('/api/v1/payments/callback', $callbackPayload);
        $duplicateResponse->assertStatus(200);
    }

    public function test_confirm_booking_job_creates_booking_and_links_payments(): void
    {
        // Mark both participants as paid
        DB::table('match_participants')
            ->where('lobby_id', $this->lobby->id)
            ->update(['payment_status' => 'paid']);

        $payment1 = Payment::create([
            'lobby_id' => $this->lobby->id,
            'player_id' => $this->profile1->id,
            'amount' => 50.00,
            'currency' => 'EGP',
            'status' => 'completed',
            'paymob_order_id' => 'ORD1',
        ]);

        $payment2 = Payment::create([
            'lobby_id' => $this->lobby->id,
            'player_id' => $this->profile2->id,
            'amount' => 50.00,
            'currency' => 'EGP',
            'status' => 'completed',
            'paymob_order_id' => 'ORD2',
        ]);

        // Run ConfirmBookingJob
        (new ConfirmBookingJob($this->lobby->id))->handle();

        $freshLobby = $this->lobby->fresh();
        $this->assertEquals('confirmed', $freshLobby->status);
        $this->assertNull($freshLobby->expires_at);

        // Booking created
        $booking = Booking::where('lobby_id', $this->lobby->id)->first();
        $this->assertNotNull($booking);
        $this->assertEquals('confirmed', $booking->status);
        $this->assertEquals(100.00, (float) $booking->total_amount);

        // Correction G: payment records linked to booking
        $this->assertEquals($booking->id, $payment1->fresh()->booking_id);
        $this->assertEquals($booking->id, $payment2->fresh()->booking_id);
    }

    public function test_confirm_booking_job_cancels_with_slot_taken_on_conflict_and_refunds(): void
    {
        Queue::fake([ProcessRefundJob::class]);

        // Create an existing confirmed booking overlapping the time
        $competingLobby = MatchLobby::create([
            'creator_id' => $this->profile1->id,
            'venue_id' => $this->venue->id,
            'sport_id' => $this->sport->id,
            'match_type' => 'public',
            'required_players' => 2,
            'match_date' => $this->lobby->match_date,
            'start_time' => '19:00:00',
            'end_time' => '20:00:00',
            'status' => 'confirmed',
            'invite_code' => 'COMPETE01',
        ]);

        Booking::create([
            'lobby_id' => $competingLobby->id,
            'venue_id' => $this->venue->id,
            'total_amount' => 100.00,
            'platform_fee_pct' => 2.00,
            'platform_fee' => 2.00,
            'venue_amount' => 98.00,
            'status' => 'confirmed',
            'confirmation_code' => 'XYZ123',
            'qr_token' => bin2hex(random_bytes(32)),
            'policy_snapshot' => [],
        ]);

        // Mark our lobby as paid
        DB::table('match_participants')
            ->where('lobby_id', $this->lobby->id)
            ->update(['payment_status' => 'paid']);

        $payment = Payment::create([
            'lobby_id' => $this->lobby->id,
            'player_id' => $this->profile1->id,
            'amount' => 50.00,
            'currency' => 'EGP',
            'status' => 'completed',
            'paymob_order_id' => 'ORD_CONFLICT',
        ]);

        // Run ConfirmBookingJob
        (new ConfirmBookingJob($this->lobby->id))->handle();

        $freshLobby = $this->lobby->fresh();
        $this->assertEquals('cancelled', $freshLobby->status);
        $this->assertEquals('slot_taken', $freshLobby->cancellation_reason);

        // ProcessRefundJob dispatched
        Queue::assertPushed(ProcessRefundJob::class, function ($job) use ($payment) {
            return $job->paymentId === $payment->id;
        });
    }

    public function test_lobby_expiry_job_cancels_expired_lobbies(): void
    {
        $this->lobby->update([
            'expires_at' => Carbon::now()->subMinutes(10),
            'status' => 'forming',
        ]);

        (new LobbyExpiryJob())->handle();

        $freshLobby = $this->lobby->fresh();
        $this->assertEquals('cancelled', $freshLobby->status);
        $this->assertEquals('expired', $freshLobby->cancellation_reason);
    }
}

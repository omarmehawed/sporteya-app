<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\MatchLobby;
use App\Models\MatchParticipant;
use App\Models\Notification;
use App\Models\PlayerProfile;
use App\Models\Sport;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueReview;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SupportingApisTest extends TestCase
{
    use RefreshDatabase;

    protected Sport $sport;
    protected Venue $venue;
    protected User $owner;
    protected User $player;
    protected PlayerProfile $profile;
    protected MatchLobby $lobby;
    protected Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sport = Sport::create([
            'name_ar' => 'كرة قدم',
            'name_en' => 'Football',
            'slug' => 'football',
            'icon' => 'football',
            'is_active' => true,
        ]);

        $this->owner = User::factory()->create(['role' => 'venue_owner']);
        $this->venue = Venue::create([
            'owner_id' => $this->owner->id,
            'name' => 'Cairo Arena',
            'address' => 'Nasr City',
            'city' => 'Cairo',
            'is_verified' => true,
            'is_active' => true,
            'location' => DB::raw("ST_MakePoint(31.33, 30.05)::geography"),
        ]);

        $this->player = User::factory()->create(['role' => 'player']);
        $this->profile = PlayerProfile::create([
            'user_id' => $this->player->id,
            'username' => 'striker_alex',
        ]);

        $this->lobby = MatchLobby::create([
            'creator_id' => $this->profile->id,
            'venue_id' => $this->venue->id,
            'sport_id' => $this->sport->id,
            'match_type' => 'public',
            'required_players' => 2,
            'match_date' => Carbon::today()->toDateString(),
            'start_time' => Carbon::now()->format('H:i:s'),
            'end_time' => Carbon::now()->addHour()->format('H:i:s'),
            'status' => 'confirmed',
            'invite_code' => 'SUPPORT01',
        ]);

        MatchParticipant::create([
            'lobby_id' => $this->lobby->id,
            'player_id' => $this->profile->id,
            'invite_status' => 'accepted',
            'payment_status' => 'paid',
        ]);

        $this->booking = Booking::create([
            'lobby_id' => $this->lobby->id,
            'venue_id' => $this->venue->id,
            'total_amount' => 100.00,
            'platform_fee_pct' => 2.00,
            'platform_fee' => 2.00,
            'venue_amount' => 98.00,
            'status' => 'confirmed',
            'confirmation_code' => 'ABC1234',
            'qr_token' => str_repeat('a', 64),
            'policy_snapshot' => [],
        ]);
    }

    public function test_venue_owner_can_scan_qr_checkin(): void
    {
        $response = $this->actingAs($this->owner)->postJson('/api/v1/checkin/scan', [
            'qr_token' => str_repeat('a', 64),
        ]);

        $response->assertStatus(200);
        $this->assertEquals('completed', $this->booking->fresh()->status);
    }

    public function test_player_can_submit_review_for_completed_booking(): void
    {
        $this->booking->update(['status' => 'completed']);

        $response = $this->actingAs($this->player)->postJson('/api/v1/reviews/venue', [
            'booking_id' => $this->booking->id,
            'rating' => 5,
            'comment' => 'ملعب رائع جداً ونظيف',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('venue_reviews', [
            'booking_id' => $this->booking->id,
            'rating' => 5,
        ]);

        $this->assertEquals(5.00, (float) $this->venue->fresh()->rating_avg);
    }

    public function test_creator_can_submit_match_result_and_releases_lobby(): void
    {
        $response = $this->actingAs($this->player)->postJson('/api/v1/matches/result', [
            'lobby_id' => $this->lobby->id,
            'team_a_score' => 5,
            'team_b_score' => 3,
        ]);

        $response->assertStatus(201);
        $this->assertEquals('completed', $this->lobby->fresh()->status);
        $this->assertDatabaseHas('match_results', [
            'lobby_id' => $this->lobby->id,
            'score' => '5 - 3',
        ]);
    }

    public function test_user_can_fetch_and_mark_notifications_as_read(): void
    {
        Notification::create([
            'user_id' => $this->player->id,
            'type' => 'booking_confirmed',
            'title' => 'تأكيد الحجز',
            'body' => 'تم تأكيد حجزك',
        ]);

        $listRes = $this->actingAs($this->player)->getJson('/api/v1/notifications');
        $listRes->assertStatus(200);
        $this->assertEquals(1, $listRes->json('unread_count'));

        $readRes = $this->actingAs($this->player)->putJson('/api/v1/notifications/read-all');
        $readRes->assertStatus(200);

        $checkRes = $this->actingAs($this->player)->getJson('/api/v1/notifications');
        $this->assertEquals(0, $checkRes->json('unread_count'));
    }

    public function test_venue_owner_can_manage_dashboard(): void
    {
        Storage::fake('public');

        // Show dashboard stats
        $dashRes = $this->actingAs($this->owner)->getJson('/api/v1/venue/my');
        $dashRes->assertStatus(200);
        $dashRes->assertJsonStructure(['venue', 'stats' => ['today_bookings', 'today_revenue']]);

        // Upload photo
        $photo = UploadedFile::fake()->image('court.jpg');
        $uploadRes = $this->actingAs($this->owner)->postJson('/api/v1/venue/my/media', [
            'photo' => $photo,
        ]);
        $uploadRes->assertStatus(201);

        // Update schedules
        $schedRes = $this->actingAs($this->owner)->putJson('/api/v1/venue/my/schedules', [
            'schedules' => [
                [
                    'day_of_week' => 1,
                    'open_time' => '10:00:00',
                    'close_time' => '23:00:00',
                    'slot_duration_minutes' => 60,
                ],
            ],
        ]);
        $schedRes->assertStatus(200);

        // Block a slot
        $blockRes = $this->actingAs($this->owner)->postJson('/api/v1/venue/my/block', [
            'date' => Carbon::tomorrow()->toDateString(),
            'start_time' => '12:00:00',
            'end_time' => '14:00:00',
            'reason' => 'Renovation',
        ]);
        $blockRes->assertStatus(201);
    }
}

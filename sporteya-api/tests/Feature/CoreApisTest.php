<?php

namespace Tests\Feature;

use App\Jobs\SendNotificationJob;
use App\Models\PlayerProfile;
use App\Models\Sport;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueBlockedSlot;
use App\Models\VenuePricing;
use App\Models\VenueSchedule;
use App\Models\VenueSport;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CoreApisTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_active_sports(): void
    {
        Sport::create([
            'name_ar' => 'تنس',
            'name_en' => 'Tennis',
            'slug' => 'tennis',
            'icon' => 'tennis',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/sports');
        $response->assertStatus(200);
        $response->assertJsonStructure(['sports']);
    }

    public function test_player_can_fetch_me_with_stats(): void
    {
        $user = User::factory()->create(['role' => 'player']);
        $profile = PlayerProfile::create([
            'user_id' => $user->id,
            'username' => 'stats_player',
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/player/me');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'player',
            'stats' => ['matches_played', 'attendance_rate'],
        ]);
    }

    public function test_player_can_update_profile_and_username_collision_returns_409(): void
    {
        $user1 = User::factory()->create(['role' => 'player']);
        PlayerProfile::create([
            'user_id' => $user1->id,
            'username' => 'legend_10',
        ]);

        $user2 = User::factory()->create(['role' => 'player']);
        $profile2 = PlayerProfile::create([
            'user_id' => $user2->id,
            'username' => 'rookie_99',
        ]);

        // Successful update without username
        $response = $this->actingAs($user2)->putJson('/api/v1/player/me', [
            'bio' => 'Midfielder looking for games',
            'city' => 'Alexandria',
        ]);
        $response->assertStatus(200);
        $this->assertEquals('Midfielder looking for games', $profile2->fresh()->bio);

        // Attempting collision on username in PUT /player/me returns 409 (Correction F)
        $collideResponse = $this->actingAs($user2)->putJson('/api/v1/player/me', [
            'username' => 'legend_10',
        ]);
        $collideResponse->assertStatus(409);
        $collideResponse->assertJson(['message' => 'هذا الاسم مأخوذ']);
    }

    public function test_can_fetch_public_profile_and_missing_username_returns_404(): void
    {
        $user = User::factory()->create(['role' => 'player']);
        PlayerProfile::create([
            'user_id' => $user->id,
            'username' => 'cairo_striker',
        ]);

        $found = $this->getJson('/api/v1/player/cairo_striker');
        $found->assertStatus(200);
        $found->assertJsonPath('player.username', 'cairo_striker');

        $notFound = $this->getJson('/api/v1/player/non_existent_username');
        $notFound->assertStatus(404);
    }

    public function test_venue_search_with_postgis_distance(): void
    {
        $owner = User::factory()->create(['role' => 'venue_owner']);
        $venue = Venue::create([
            'owner_id' => $owner->id,
            'name' => 'Tahrir Courts',
            'address' => 'Downtown',
            'city' => 'Cairo',
            'is_verified' => true,
            'is_active' => true,
            'location' => DB::raw("ST_MakePoint(31.2357, 30.0444)::geography"),
        ]);

        // Search near Tahrir square (approx 0 distance)
        $response = $this->getJson('/api/v1/venues?latitude=30.0444&longitude=31.2357&radius=10');
        $response->assertStatus(200);
        $response->assertJsonStructure(['venues', 'page']);
        $this->assertNotEmpty($response->json('venues'));
    }

    public function test_venue_availability_generates_slots_and_excludes_blocked(): void
    {
        $owner = User::factory()->create(['role' => 'venue_owner']);
        $venue = Venue::create([
            'owner_id' => $owner->id,
            'name' => 'Smouha Club',
            'address' => 'Smouha',
            'city' => 'Alexandria',
            'is_verified' => true,
            'is_active' => true,
            'location' => DB::raw("ST_MakePoint(29.95, 31.20)::geography"),
        ]);

        $testDate = '2026-10-15'; // Thursday
        $dayOfWeek = Carbon::parse($testDate)->dayOfWeek;

        // Schedule: open 16:00 to 19:00 with 60min slots -> 16:00-17:00, 17:00-18:00, 18:00-19:00
        VenueSchedule::create([
            'venue_id' => $venue->id,
            'day_of_week' => $dayOfWeek,
            'open_time' => '16:00:00',
            'close_time' => '19:00:00',
            'slot_duration_minutes' => 60,
            'is_closed' => false,
        ]);

        // Block middle slot 17:00-18:00
        VenueBlockedSlot::create([
            'venue_id' => $venue->id,
            'date' => $testDate,
            'start_time' => '17:00:00',
            'end_time' => '18:00:00',
            'reason' => 'Maintenance',
        ]);

        $response = $this->getJson("/api/v1/venues/{$venue->id}/availability?date={$testDate}");
        $response->assertStatus(200);
        $slots = $response->json('slots');

        $this->assertCount(2, $slots);
        $this->assertEquals('16:00:00', $slots[0]['start_time']);
        $this->assertEquals('18:00:00', $slots[1]['start_time']);
    }

    public function test_booking_group_crud_and_invite_member_dispatches_notification(): void
    {
        Queue::fake();

        $sport = Sport::first() ?? Sport::create([
            'name_ar' => 'كرة قدم',
            'name_en' => 'Football',
            'slug' => 'football',
            'icon' => 'football',
            'is_active' => true,
        ]);

        $user1 = User::factory()->create(['role' => 'player']);
        $profile1 = PlayerProfile::create([
            'user_id' => $user1->id,
            'username' => 'group_captain',
        ]);

        $user2 = User::factory()->create(['role' => 'player']);
        $profile2 = PlayerProfile::create([
            'user_id' => $user2->id,
            'username' => 'new_recruit',
        ]);

        // 1. Create group
        $createRes = $this->actingAs($user1)->postJson('/api/v1/booking-groups', [
            'name' => 'Friday Strikers',
            'sport_id' => $sport->id,
        ]);
        $createRes->assertStatus(201);
        $groupId = $createRes->json('booking_group.id');

        // 2. Add member by username
        $inviteRes = $this->actingAs($user1)->postJson("/api/v1/booking-groups/{$groupId}/members", [
            'username' => 'new_recruit',
        ]);
        $inviteRes->assertStatus(201);

        Queue::assertPushed(SendNotificationJob::class, function ($job) use ($user2) {
            return in_array($user2->id, $job->userIds) && $job->type === 'group_invite';
        });

        // 3. List groups
        $listRes = $this->actingAs($user2)->getJson('/api/v1/booking-groups');
        $listRes->assertStatus(200);
        $this->assertCount(1, $listRes->json('booking_groups'));
    }
}

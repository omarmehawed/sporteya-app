<?php

namespace Tests\Feature;

use App\Jobs\SendNotificationJob;
use App\Models\PlayerProfile;
use App\Models\Sport;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_player_can_submit_sports_selection(): void
    {
        $sport = Sport::first() ?? Sport::create([
            'name_ar' => 'كرة قدم',
            'name_en' => 'Football',
            'slug' => 'football',
            'icon' => 'football',
            'is_active' => true,
        ]);

        $user = User::factory()->create(['role' => 'player', 'setup_complete' => false]);
        $profile = PlayerProfile::create([
            'user_id' => $user->id,
            'username' => 'test_player',
            'onboarding_done' => false,
        ]);

        $response = $this->actingAs($user)->postJson('/api/v1/onboarding/sports', [
            'sports' => [
                [
                    'sport_id' => $sport->id,
                    'skill_level' => 'intermediate',
                    'is_preferred' => true,
                ],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['player_sports']);
        $this->assertDatabaseHas('player_sports', [
            'player_id' => $profile->id,
            'sport_id' => $sport->id,
            'skill_level' => 'intermediate',
            'is_preferred' => true,
        ]);
    }

    public function test_player_can_choose_custom_username_during_onboarding(): void
    {
        $user = User::factory()->create(['role' => 'player']);
        $profile = PlayerProfile::create([
            'user_id' => $user->id,
            'username' => 'initial_username',
        ]);

        $response = $this->actingAs($user)->postJson('/api/v1/onboarding/username', [
            'username' => 'new_striker_99',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['username' => 'new_striker_99']);
        $this->assertDatabaseHas('player_profiles', [
            'id' => $profile->id,
            'username' => 'new_striker_99',
        ]);
    }

    public function test_choosing_existing_username_returns_422_with_arabic_message_and_no_retry(): void
    {
        $existingUser = User::factory()->create(['role' => 'player']);
        PlayerProfile::create([
            'user_id' => $existingUser->id,
            'username' => 'taken_name',
        ]);

        $user = User::factory()->create(['role' => 'player']);
        $profile = PlayerProfile::create([
            'user_id' => $user->id,
            'username' => 'my_name',
        ]);

        $response = $this->actingAs($user)->postJson('/api/v1/onboarding/username', [
            'username' => 'taken_name',
        ]);

        $response->assertStatus(422);
        $response->assertJson(['message' => 'هذا الاسم مأخوذ']);
    }

    public function test_player_can_update_location_with_postgis_coordinates(): void
    {
        $user = User::factory()->create(['role' => 'player']);
        $profile = PlayerProfile::create([
            'user_id' => $user->id,
            'username' => 'geo_player',
        ]);

        $response = $this->actingAs($user)->postJson('/api/v1/onboarding/location', [
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'city' => 'Cairo',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('player_profiles', [
            'id' => $profile->id,
            'city' => 'Cairo',
        ]);
    }

    public function test_player_can_upload_avatar(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['role' => 'player']);
        PlayerProfile::create([
            'user_id' => $user->id,
            'username' => 'avatar_player',
        ]);

        $file = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->actingAs($user)->postJson('/api/v1/onboarding/avatar', [
            'avatar' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['avatar_url']);
    }

    public function test_player_completing_onboarding_updates_profile_and_user_atomically(): void
    {
        $user = User::factory()->create(['role' => 'player', 'setup_complete' => false]);
        $profile = PlayerProfile::create([
            'user_id' => $user->id,
            'username' => 'ready_player',
            'onboarding_done' => false,
        ]);

        $response = $this->actingAs($user)->putJson('/api/v1/onboarding/complete');

        $response->assertStatus(200);
        $response->assertJson(['setup_complete' => true]);

        $this->assertTrue($profile->fresh()->onboarding_done);
        $this->assertTrue($user->fresh()->setup_complete);
    }

    public function test_venue_owner_can_create_venue_and_complete_setup(): void
    {
        $sport = Sport::first() ?? Sport::create([
            'name_ar' => 'بادل',
            'name_en' => 'Padel',
            'slug' => 'padel',
            'icon' => 'padel',
            'is_active' => true,
        ]);

        $owner = User::factory()->create(['role' => 'venue_owner', 'setup_complete' => false]);

        $response = $this->actingAs($owner)->postJson('/api/v1/venues', [
            'name' => 'Al Ahly Padel Club',
            'description' => 'Best padel courts in town',
            'address' => 'Nasr City',
            'city' => 'Cairo',
            'latitude' => 30.0500,
            'longitude' => 31.3300,
            'phone' => '01012345678',
            'has_lighting' => true,
            'has_parking' => true,
            'sports' => [
                [
                    'sport_id' => $sport->id,
                    'court_count' => 4,
                    'players_per_team' => 2,
                ],
            ],
        ]);

        $response->assertStatus(201);
        $response->assertJson(['setup_complete' => true]);

        $this->assertDatabaseHas('venues', [
            'name' => 'Al Ahly Padel Club',
            'owner_id' => $owner->id,
            'is_verified' => false,
            'is_active' => false,
        ]);

        $this->assertTrue($owner->fresh()->setup_complete);
    }

    public function test_admin_can_verify_venue_and_notification_is_dispatched(): void
    {
        Queue::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'venue_owner']);

        $venue = Venue::create([
            'owner_id' => $owner->id,
            'name' => 'Olympic Club',
            'address' => 'Zamalek',
            'city' => 'Cairo',
            'is_verified' => false,
            'is_active' => false,
            'location' => DB::raw("ST_MakePoint(31.22, 30.06)::geography"),
        ]);

        $response = $this->actingAs($admin)->putJson("/api/v1/admin/venues/{$venue->id}/verify", [
            'is_verified' => true,
            'is_active' => true,
        ]);

        $response->assertStatus(200);
        $this->assertTrue($venue->fresh()->is_verified);
        $this->assertTrue($venue->fresh()->is_active);

        Queue::assertPushed(SendNotificationJob::class, function ($job) use ($owner) {
            return in_array($owner->id, $job->userIds) && $job->type === 'venue_verified';
        });
    }
}

<?php

namespace Tests\Feature;

use App\Events\LobbyStatusChanged;
use App\Models\CancellationPolicy;
use App\Models\MatchLobby;
use App\Models\PlayerProfile;
use App\Models\Sport;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueSport;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class MatchLobbyTest extends TestCase
{
    use RefreshDatabase;

    protected Sport $sport;
    protected Venue $venue;
    protected User $creatorUser;
    protected PlayerProfile $creatorProfile;

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

        $owner = User::factory()->create(['role' => 'venue_owner']);
        $this->venue = Venue::create([
            'owner_id' => $owner->id,
            'name' => 'Wembley Cairo',
            'address' => 'Zamalek',
            'city' => 'Cairo',
            'is_verified' => true,
            'is_active' => true,
            'location' => DB::raw("ST_MakePoint(31.22, 30.06)::geography"),
        ]);

        VenueSport::create([
            'venue_id' => $this->venue->id,
            'sport_id' => $this->sport->id,
            'court_count' => 2,
            'players_per_team' => 5,
        ]);

        CancellationPolicy::create([
            'venue_id' => $this->venue->id,
            'full_refund_hours' => 24,
            'half_refund_hours' => 12,
            'no_refund_hours' => 2,
        ]);

        $this->creatorUser = User::factory()->create(['role' => 'player']);
        $this->creatorProfile = PlayerProfile::create([
            'user_id' => $this->creatorUser->id,
            'username' => 'lobby_creator',
        ]);
    }

    public function test_player_can_create_match_lobby_and_slot_warning_is_computed(): void
    {
        $matchDate = Carbon::tomorrow()->toDateString();

        $response = $this->actingAs($this->creatorUser)->postJson('/api/v1/lobbies', [
            'venue_id' => $this->venue->id,
            'sport_id' => $this->sport->id,
            'match_type' => 'public',
            'required_players' => 10,
            'match_date' => $matchDate,
            'start_time' => '18:00:00',
            'end_time' => '19:00:00',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['lobby', 'invite_code', 'slot_warning']);
        $this->assertFalse($response->json('slot_warning'));

        $lobbyId = $response->json('lobby.id');
        $lobby = MatchLobby::find($lobbyId);
        $this->assertNotNull($lobby);
        $this->assertEquals('forming', $lobby->status);
        $this->assertNotNull($lobby->expires_at);
        $this->assertNotNull($lobby->policy_snapshot);
        $this->assertEquals(24, $lobby->policy_snapshot['full_refund_hours']);

        // Check creator is accepted participant
        $this->assertDatabaseHas('match_participants', [
            'lobby_id' => $lobbyId,
            'player_id' => $this->creatorProfile->id,
            'invite_status' => 'accepted',
        ]);

        // Second lobby created on overlapping time has slot_warning = true (Correction V)
        $user2 = User::factory()->create(['role' => 'player']);
        PlayerProfile::create([
            'user_id' => $user2->id,
            'username' => 'second_creator',
        ]);

        $competingRes = $this->actingAs($user2)->postJson('/api/v1/lobbies', [
            'venue_id' => $this->venue->id,
            'sport_id' => $this->sport->id,
            'match_type' => 'public',
            'required_players' => 10,
            'match_date' => $matchDate,
            'start_time' => '18:30:00', // overlaps 18:00 - 19:00
            'end_time' => '19:30:00',
        ]);

        $competingRes->assertStatus(201);
        $this->assertTrue($competingRes->json('slot_warning'));
    }

    public function test_player_can_join_public_lobby(): void
    {
        $matchDate = Carbon::tomorrow()->toDateString();

        $lobby = MatchLobby::create([
            'creator_id' => $this->creatorProfile->id,
            'venue_id' => $this->venue->id,
            'sport_id' => $this->sport->id,
            'match_type' => 'public',
            'required_players' => 4,
            'match_date' => $matchDate,
            'start_time' => '20:00:00',
            'end_time' => '21:00:00',
            'status' => 'forming',
            'invite_code' => 'TESTCODE01',
            'expires_at' => Carbon::now()->addHours(2),
        ]);

        $joiner = User::factory()->create(['role' => 'player']);
        $joinerProfile = PlayerProfile::create([
            'user_id' => $joiner->id,
            'username' => 'midfield_joiner',
        ]);

        $response = $this->actingAs($joiner)->postJson("/api/v1/lobbies/{$lobby->id}/join");
        $response->assertStatus(200);

        $this->assertDatabaseHas('match_participants', [
            'lobby_id' => $lobby->id,
            'player_id' => $joinerProfile->id,
            'invite_status' => 'accepted',
        ]);
    }

    public function test_creator_can_cancel_lobby_and_event_is_broadcast(): void
    {
        Event::fake([LobbyStatusChanged::class]);

        $lobby = MatchLobby::create([
            'creator_id' => $this->creatorProfile->id,
            'venue_id' => $this->venue->id,
            'sport_id' => $this->sport->id,
            'match_type' => 'public',
            'required_players' => 4,
            'match_date' => Carbon::tomorrow()->toDateString(),
            'start_time' => '20:00:00',
            'end_time' => '21:00:00',
            'status' => 'forming',
            'invite_code' => 'CANCELCODE',
            'expires_at' => Carbon::now()->addHours(2),
        ]);

        $response = $this->actingAs($this->creatorUser)->deleteJson("/api/v1/lobbies/{$lobby->id}");
        $response->assertStatus(200);

        $fresh = $lobby->fresh();
        $this->assertEquals('cancelled', $fresh->status);
        $this->assertEquals('creator_cancelled', $fresh->cancellation_reason);

        Event::assertDispatched(LobbyStatusChanged::class);
    }
}

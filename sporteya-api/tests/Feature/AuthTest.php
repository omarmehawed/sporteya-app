<?php

namespace Tests\Feature;

use App\Models\PlayerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_player_can_register_and_profile_is_created_with_auto_username(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Omar Ahmed',
            'email' => 'omar@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'player',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'name', 'email', 'role', 'setup_complete'],
                'profile' => ['id', 'user_id', 'username', 'player_status'],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'omar@example.com',
            'role' => 'player',
            'setup_complete' => false,
        ]);

        $this->assertDatabaseHas('player_profiles', [
            'username' => 'omar_ahmed',
            'player_status' => 'available',
        ]);
    }

    public function test_multiple_registrations_with_same_name_get_unique_usernames_without_error(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->postJson('/api/v1/auth/register', [
                'name' => 'Karim Ali',
                'email' => "karim{$i}@example.com",
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'player',
            ]);

            $response->assertStatus(201);
        }

        $usernames = PlayerProfile::pluck('username')->toArray();
        $this->assertCount(5, $usernames);
        $this->assertCount(5, array_unique($usernames));
        $this->assertContains('karim_ali', $usernames);
    }

    public function test_venue_owner_can_register_without_player_profile(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Mahmoud Arena',
            'email' => 'arena@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'venue_owner',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', [
            'email' => 'arena@example.com',
            'role' => 'venue_owner',
            'setup_complete' => false,
        ]);
        $this->assertDatabaseMissing('player_profiles', [
            'user_id' => $response->json('user.id'),
        ]);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'login_test@example.com',
            'password' => bcrypt('secret123'),
            'role' => 'player',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'login_test@example.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'email', 'role'],
            ]);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'wrong_pw@example.com',
            'password' => bcrypt('correct_password'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'wrong_pw@example.com',
            'password' => 'incorrect_password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_authenticated_user_can_fetch_me_and_setup_complete(): void
    {
        $user = User::factory()->create([
            'role' => 'player',
            'setup_complete' => false,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'setup_complete' => false,
                'user' => [
                    'id' => $user->id,
                    'role' => 'player',
                ],
            ]);
    }

    public function test_user_can_logout_and_revoke_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Logged out successfully.']);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_admin_cannot_login_via_google_oauth(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@sporteya.com',
            'role' => 'admin',
        ]);

        $response = $this->postJson('/api/v1/auth/google', [
            'email' => 'admin@sporteya.com',
            'google_id' => 'google-admin-12345',
        ]);

        $response->assertStatus(403)
            ->assertJson(['message' => 'Admin must use password login.']);
    }

    public function test_create_admin_artisan_command_creates_admin(): void
    {
        $this->artisan('sporteya:create-admin admin_artisan@sporteya.com "System Administrator"')
            ->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'email' => 'admin_artisan@sporteya.com',
            'role' => 'admin',
            'setup_complete' => true,
        ]);
    }
}

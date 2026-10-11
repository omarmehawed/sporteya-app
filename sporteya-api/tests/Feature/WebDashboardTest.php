<?php

namespace Tests\Feature;

use App\Models\CancellationPolicy;
use App\Models\Sport;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueBlockedSlot;
use App\Models\VenuePricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\SportsSeeder::class);
    }

    public function test_login_page_renders_successfully()
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('تسجيل الدخول للوحة التحكم');
    }

    public function test_venue_owner_can_login_and_redirects_to_dashboard()
    {
        $owner = User::factory()->create([
            'email' => 'owner@test.com',
            'role' => 'venue_owner',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'owner@test.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard.index'));
        $this->assertAuthenticatedAs($owner);
    }

    public function test_admin_can_login_and_redirects_to_admin_venues()
    {
        $admin = User::factory()->create([
            'email' => 'admin@test.com',
            'role' => 'admin',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('admin.venues'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_player_cannot_login_to_web_dashboard()
    {
        User::factory()->create([
            'email' => 'player@test.com',
            'role' => 'player',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'player@test.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_venue_owner_can_access_dashboard_and_sections()
    {
        $owner = User::factory()->create(['role' => 'venue_owner']);
        $venue = Venue::create([
            'owner_id' => $owner->id,
            'name' => 'Al Ahly Arena',
            'city' => 'Cairo',
            'address' => 'Nasr City',
            'phone' => '+201011111111',
            'is_verified' => true,
            'is_active' => true,
            'location' => \Illuminate\Support\Facades\DB::raw("ST_MakePoint(31.2357, 30.0444)::geography"),
        ]);
        CancellationPolicy::create([
            'venue_id' => $venue->id,
            'full_refund_hours' => 24,
            'half_refund_hours' => 12,
            'no_refund_hours' => 6,
        ]);

        // Dashboard overview
        $response = $this->actingAs($owner)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Al Ahly Arena');

        // Availability
        $response = $this->actingAs($owner)->get('/dashboard/availability');
        $response->assertStatus(200);
        $response->assertSee('جدول مواعيد العمل الأسبوعية');

        // Pricing
        $response = $this->actingAs($owner)->get('/dashboard/pricing');
        $response->assertStatus(200);
        $response->assertSee('إضافة تسعيرة جديدة');

        // Bookings
        $response = $this->actingAs($owner)->get('/dashboard/bookings');
        $response->assertStatus(200);
        $response->assertSee('سجل حجوزات الملعب');

        // Settings
        $response = $this->actingAs($owner)->get('/dashboard/settings');
        $response->assertStatus(200);
        $response->assertSee('بيانات الملعب وسياسة الإلغاء');

        // Reviews
        $response = $this->actingAs($owner)->get('/dashboard/reviews');
        $response->assertStatus(200);
        $response->assertSee('تقييم الملعب العام');
    }

    public function test_venue_owner_can_manage_schedules_and_blocked_slots()
    {
        $owner = User::factory()->create(['role' => 'venue_owner']);
        $venue = Venue::create([
            'owner_id' => $owner->id,
            'name' => 'Zamalek Arena',
            'city' => 'Giza',
            'address' => 'Mohandessin',
            'location' => \Illuminate\Support\Facades\DB::raw("ST_MakePoint(31.2357, 30.0444)::geography"),
        ]);

        // Save schedules
        $response = $this->actingAs($owner)->post('/dashboard/schedules', [
            'schedules' => [
                0 => ['open_time' => '09:00', 'close_time' => '22:00'],
                1 => ['open_time' => '10:00', 'close_time' => '23:00', 'is_closed' => '1'],
            ],
        ]);
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('venue_schedules', [
            'venue_id' => $venue->id,
            'day_of_week' => 0,
            'open_time' => '09:00:00',
        ]);

        // Block slot
        $response = $this->actingAs($owner)->post('/dashboard/slots/block', [
            'date' => now()->addDay()->toDateString(),
            'start_time' => '18:00',
            'end_time' => '20:00',
            'reason' => 'صيانة الإضاءة',
        ]);
        $response->assertSessionHas('success');
        $slot = VenueBlockedSlot::first();
        $this->assertNotNull($slot);
        $this->assertEquals('صيانة الإضاءة', $slot->reason);

        // Delete blocked slot
        $response = $this->actingAs($owner)->delete('/dashboard/slots/' . $slot->id);
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('venue_blocked_slots', ['id' => $slot->id]);
    }

    public function test_venue_owner_can_manage_pricing_and_settings()
    {
        $owner = User::factory()->create(['role' => 'venue_owner']);
        $venue = Venue::create([
            'owner_id' => $owner->id,
            'name' => 'Pyramids Arena',
            'city' => 'Cairo',
            'address' => 'New Cairo',
            'location' => \Illuminate\Support\Facades\DB::raw("ST_MakePoint(31.2357, 30.0444)::geography"),
        ]);
        $sport = Sport::first();

        // Add pricing
        $response = $this->actingAs($owner)->post('/dashboard/pricing', [
            'sport_id' => $sport->id,
            'day_of_week' => 5, // Friday
            'start_time' => '16:00',
            'end_time' => '23:00',
            'price_per_hour' => 350.00,
        ]);
        $response->assertSessionHas('success');
        $pricing = VenuePricing::first();
        $this->assertNotNull($pricing);
        $this->assertEquals(350.00, $pricing->price_per_hour);

        // Delete pricing
        $response = $this->actingAs($owner)->delete('/dashboard/pricing/' . $pricing->id);
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('venue_pricing', ['id' => $pricing->id]);

        // Update settings and cancellation policy
        $response = $this->actingAs($owner)->post('/dashboard/settings', [
            'name' => 'Pyramids Elite Arena',
            'description' => 'ملعب نجيل صناعي حديث',
            'address' => 'التجمع الخامس',
            'city' => 'القاهرة',
            'phone' => '+201099999999',
            'has_lighting' => '1',
            'has_changing_room' => '1',
            'full_refund_hours' => 48,
            'half_refund_hours' => 24,
            'no_refund_hours' => 12,
        ]);
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('venues', [
            'id' => $venue->id,
            'name' => 'Pyramids Elite Arena',
            'has_lighting' => true,
        ]);
        $this->assertDatabaseHas('cancellation_policies', [
            'venue_id' => $venue->id,
            'full_refund_hours' => 48,
        ]);
    }

    public function test_admin_can_manage_venues()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'venue_owner']);
        $venue = Venue::create([
            'owner_id' => $owner->id,
            'name' => 'Cairo Club',
            'city' => 'Cairo',
            'address' => 'Maadi',
            'is_verified' => false,
            'is_active' => false,
            'location' => \Illuminate\Support\Facades\DB::raw("ST_MakePoint(31.2357, 30.0444)::geography"),
        ]);

        // List venues
        $response = $this->actingAs($admin)->get('/admin/venues');
        $response->assertStatus(200);
        $response->assertSee('Cairo Club');

        // View venue detail
        $response = $this->actingAs($admin)->get('/admin/venues/' . $venue->id);
        $response->assertStatus(200);
        $response->assertSee('Cairo Club');

        // Approve venue
        $response = $this->actingAs($admin)->post('/admin/venues/' . $venue->id . '/verify', [
            'action' => 'approve',
        ]);
        $response->assertSessionHas('success');
        $venue->refresh();
        $this->assertTrue((bool)$venue->is_verified);
        $this->assertTrue((bool)$venue->is_active);

        // Toggle active
        $response = $this->actingAs($admin)->post('/admin/venues/' . $venue->id . '/verify', [
            'action' => 'toggle_active',
        ]);
        $response->assertSessionHas('success');
        $venue->refresh();
        $this->assertFalse((bool)$venue->is_active);
    }

    public function test_unauthorized_user_cannot_access_owner_or_admin_routes()
    {
        $player = User::factory()->create(['role' => 'player']);

        $response = $this->actingAs($player)->get('/dashboard');
        $response->assertStatus(403);

        $response = $this->actingAs($player)->get('/admin/venues');
        $response->assertStatus(403);
    }
}

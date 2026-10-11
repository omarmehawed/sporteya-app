<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Correction T: full bookings table structure
        Schema::create('bookings', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));
            // One booking per lobby (confirmed lobbies only)
            $table->uuid('lobby_id')->unique()->references('id')
                  ->on('match_lobbies');
            $table->uuid('venue_id')->references('id')->on('venues');
            // Financial summary
            $table->decimal('total_amount', 10, 2)->unsigned();
            $table->decimal('platform_fee_pct', 5, 2)->default(2.00);
            $table->decimal('platform_fee', 10, 2)->unsigned();
            $table->decimal('venue_amount', 10, 2)->unsigned();
            $table->string('currency', 3)->default('EGP');
            $table->enum('status', ['confirmed', 'cancelled', 'completed'])
                  ->default('confirmed');
            // Human-readable code shown to venue owner at check-in
            $table->string('confirmation_code', 10)->unique();
            // QR token: bin2hex(random_bytes(32)) — 64 chars, never derived from ID
            $table->string('qr_token', 64)->unique();
            // Snapshot of cancellation policy at lobby creation time
            // Used for all refund calculations — never re-read from policy table
            $table->jsonb('policy_snapshot');
            // Cancellation tracking
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->uuid('cancelled_by')->nullable()
                  ->references('id')->on('users');
            $table->timestamps();
        });

        // Lookup bookings by venue for availability checks
        DB::statement('CREATE INDEX idx_bookings_venue_date
          ON bookings(venue_id, status)');
        // ConfirmBookingJob slot conflict query joins on lobby_id
        DB::statement('CREATE INDEX idx_bookings_lobby
          ON bookings(lobby_id)');
        // QR check-in lookup (venue owner scans token)
        DB::statement('CREATE INDEX idx_bookings_qr
          ON bookings(qr_token)');
        // Human-readable code lookup
        DB::statement('CREATE INDEX idx_bookings_code
          ON bookings(confirmation_code)');
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Correction R: full match_lobbies structure with all required fields
        Schema::create('match_lobbies', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));
            // creator_id: the player who created this lobby
            $table->uuid('creator_id')->references('id')->on('player_profiles');
            $table->uuid('venue_id')->references('id')->on('venues');
            $table->uuid('sport_id')->references('id')->on('sports');
            // Optional: pre-fill with group members on creation
            $table->uuid('booking_group_id')->nullable()->references('id')->on('booking_groups');
            $table->enum('match_type', ['private', 'public', 'mixed'])->default('mixed');
            $table->unsignedSmallInteger('required_players');
            $table->date('match_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('status', [
                'forming',
                'payment_pending',
                'confirmed',
                'cancelled',
                'completed'
            ])->default('forming');
            $table->string('invite_code', 20)->unique();
            $table->text('notes')->nullable();
            // cancellation_reason: set by LobbyExpiryJob ('expired'),
            // DELETE /lobbies/:id ('creator_cancelled'),
            // ConfirmBookingJob ('slot_taken')
            $table->string('cancellation_reason', 100)->nullable();
            $table->timestamp('cancelled_at')->nullable();
            // policy_snapshot: captured at lobby creation from cancellation_policies
            // Used for all refund calculations (never re-read from the policy table)
            $table->jsonb('policy_snapshot')->nullable();
            // Correction 4: default = NOW() + 2h, set via raw SQL below
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        // Set expires_at default to NOW() + 2 hours
        DB::statement("ALTER TABLE match_lobbies
          ALTER COLUMN expires_at
          SET DEFAULT (NOW() + INTERVAL '2 hours')");

        // Efficient LobbyExpiryJob query
        DB::statement('CREATE INDEX idx_lobby_expiry
          ON match_lobbies(expires_at, status)');
        // Slot conflict check in ConfirmBookingJob and POST /lobbies
        DB::statement('CREATE INDEX idx_lobby_slot
          ON match_lobbies(venue_id, match_date, start_time, status)');
        DB::statement('CREATE INDEX idx_lobby_creator
          ON match_lobbies(creator_id)');
        DB::statement('CREATE INDEX idx_lobby_invite
          ON match_lobbies(invite_code)');
        DB::statement('CREATE INDEX idx_lobby_public
          ON match_lobbies(status, match_type)');
    }

    public function down(): void
    {
        Schema::dropIfExists('match_lobbies');
    }
};

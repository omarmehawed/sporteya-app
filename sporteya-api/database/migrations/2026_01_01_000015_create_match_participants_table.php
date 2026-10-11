<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Correction Q: full match_participants structure
        Schema::create('match_participants', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));
            $table->uuid('lobby_id')->references('id')->on('match_lobbies')
                  ->onDelete('cascade');
            $table->uuid('player_id')->references('id')->on('player_profiles')
                  ->onDelete('cascade');
            $table->enum('invite_status', [
                'invited',
                'accepted',
                'declined',
                'pending_approval'
            ])->default('invited');
            // Correction L: this column is updated by the Paymob webhook handler
            // and by ProcessRefundJob. ConfirmBookingJob counts WHERE payment_status='paid'.
            $table->enum('payment_status', [
                'pending',
                'paid',
                'refunded',
                'waived'
            ])->default('pending');
            $table->decimal('payment_amount', 10, 2)->nullable();
            $table->uuid('invited_by')->nullable()->references('id')
                  ->on('player_profiles');
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamps();

            // One participant record per player per lobby
            $table->unique(['lobby_id', 'player_id']);
        });

        DB::statement('CREATE INDEX idx_participants_lobby
          ON match_participants(lobby_id)');
        DB::statement('CREATE INDEX idx_participants_player
          ON match_participants(player_id)');
        // Composite index for the ConfirmBookingJob count query:
        // WHERE lobby_id = ? AND invite_status = 'accepted' AND payment_status = 'paid'
        DB::statement('CREATE INDEX idx_participants_confirm
          ON match_participants(lobby_id, invite_status, payment_status)');
    }

    public function down(): void
    {
        Schema::dropIfExists('match_participants');
    }
};

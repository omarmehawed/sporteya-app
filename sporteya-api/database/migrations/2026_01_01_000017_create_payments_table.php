<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Correction M: lobby_id NOT NULL, booking_id nullable
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));
            // Correction G: lobby always known at payment time
            $table->uuid('lobby_id')->references('id')->on('match_lobbies');
            $table->uuid('player_id')->references('id')->on('player_profiles');
            // Correction G: nullable until ConfirmBookingJob sets it
            $table->uuid('booking_id')->nullable()->references('id')->on('bookings');
            $table->decimal('amount', 10, 2)->unsigned();
            $table->string('currency', 3)->default('EGP');
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])
                  ->default('pending');
            $table->string('payment_method', 50)->nullable();
            $table->string('paymob_order_id', 200)->nullable();
            // Correction J: unique index for idempotency
            $table->string('paymob_txn_id', 200)->nullable()->unique();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->decimal('refund_amount', 10, 2)->nullable();
            $table->timestamps();
        });

        // Indexes
        DB::statement('CREATE INDEX idx_payments_lobby ON payments(lobby_id)');
        DB::statement('CREATE INDEX idx_payments_player ON payments(player_id)');
        DB::statement('CREATE INDEX idx_payments_booking ON payments(booking_id)');
        // Note: paymob_txn_id unique index is created by ->unique() above
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

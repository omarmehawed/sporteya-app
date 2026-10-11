<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venue_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));
            $table->uuid('booking_id')->unique()->references('id')->on('bookings')->onDelete('cascade');
            $table->uuid('venue_id')->references('id')->on('venues')->onDelete('cascade');
            $table->uuid('player_id')->references('id')->on('player_profiles')->onDelete('cascade');
            $table->unsignedTinyInteger('rating'); // 1-5
            $table->text('comment')->nullable();
            $table->timestamps();
        });

        DB::statement('CREATE INDEX idx_reviews_venue ON venue_reviews(venue_id)');
        DB::statement('CREATE INDEX idx_reviews_player ON venue_reviews(player_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_reviews');
    }
};

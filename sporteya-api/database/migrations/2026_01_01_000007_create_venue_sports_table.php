<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venue_sports', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));
            $table->uuid('venue_id')->references('id')->on('venues')->onDelete('cascade');
            $table->uuid('sport_id')->references('id')->on('sports')->onDelete('cascade');
            $table->unsignedSmallInteger('court_count')->default(1);
            $table->timestamps();

            $table->unique(['venue_id', 'sport_id']);
        });

        DB::statement('CREATE INDEX idx_venue_sports_venue ON venue_sports(venue_id)');
        DB::statement('CREATE INDEX idx_venue_sports_sport ON venue_sports(sport_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_sports');
    }
};

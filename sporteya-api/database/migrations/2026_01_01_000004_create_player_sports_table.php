<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_sports', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));
            $table->uuid('player_id')->references('id')->on('player_profiles')->onDelete('cascade');
            $table->uuid('sport_id')->references('id')->on('sports')->onDelete('cascade');
            $table->enum('skill_level', ['beginner', 'intermediate', 'advanced', 'expert'])->default('intermediate');
            $table->boolean('is_preferred')->default(false);
            $table->timestamps();

            $table->unique(['player_id', 'sport_id']);
        });

        DB::statement('CREATE INDEX idx_player_sports_player ON player_sports(player_id)');
        DB::statement('CREATE INDEX idx_player_sports_sport ON player_sports(sport_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('player_sports');
    }
};

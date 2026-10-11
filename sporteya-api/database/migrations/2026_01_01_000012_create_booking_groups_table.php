<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_groups', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));
            $table->uuid('creator_id')->references('id')->on('player_profiles')->onDelete('cascade');
            $table->uuid('sport_id')->references('id')->on('sports')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('invite_code', 20)->unique();
            $table->timestamps();
        });

        DB::statement('CREATE INDEX idx_booking_groups_creator ON booking_groups(creator_id)');
        DB::statement('CREATE INDEX idx_booking_groups_sport ON booking_groups(sport_id)');
        DB::statement('CREATE INDEX idx_booking_groups_invite ON booking_groups(invite_code)');
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_groups');
    }
};

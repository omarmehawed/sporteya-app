<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));
            $table->uuid('user_id')->unique()->references('id')->on('users')->onDelete('cascade');
            $table->string('username')->unique();
            $table->string('phone')->nullable();
            $table->string('avatar_url')->nullable();
            $table->text('bio')->nullable();
            $table->enum('player_status', ['available', 'booked'])->default('available');
            $table->boolean('onboarding_done')->default(false);
            $table->timestamps();
        });

        // Correction 5: PostGIS column managed via raw DB::statement()
        DB::statement("ALTER TABLE player_profiles ADD COLUMN location GEOGRAPHY(POINT, 4326) NULL");
        DB::statement("CREATE INDEX idx_player_location ON player_profiles USING GIST(location)");
        DB::statement("CREATE INDEX idx_player_status ON player_profiles(player_status)");
    }

    public function down(): void
    {
        Schema::dropIfExists('player_profiles');
    }
};

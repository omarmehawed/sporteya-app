<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_group_members', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));
            $table->uuid('booking_group_id')->references('id')->on('booking_groups')->onDelete('cascade');
            $table->uuid('player_id')->references('id')->on('player_profiles')->onDelete('cascade');
            $table->enum('role', ['admin', 'member'])->default('member');
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamps();

            $table->unique(['booking_group_id', 'player_id']);
        });

        DB::statement('CREATE INDEX idx_group_members_group ON booking_group_members(booking_group_id)');
        DB::statement('CREATE INDEX idx_group_members_player ON booking_group_members(player_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_group_members');
    }
};

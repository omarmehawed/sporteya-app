<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venue_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));
            $table->uuid('venue_id')->references('id')->on('venues')->onDelete('cascade');
            $table->unsignedTinyInteger('day_of_week'); // 0 = Sunday, ..., 6 = Saturday
            $table->time('open_time');
            $table->time('close_time');
            $table->unsignedSmallInteger('slot_duration_minutes')->default(60);
            $table->boolean('is_closed')->default(false);
            $table->timestamps();

            $table->unique(['venue_id', 'day_of_week']);
        });

        DB::statement('CREATE INDEX idx_venue_schedules_lookup ON venue_schedules(venue_id, day_of_week)');
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_schedules');
    }
};

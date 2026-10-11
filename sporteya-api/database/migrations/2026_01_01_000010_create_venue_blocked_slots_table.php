<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venue_blocked_slots', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));
            $table->uuid('venue_id')->references('id')->on('venues')->onDelete('cascade');
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('reason')->nullable();
            $table->timestamps();
        });

        DB::statement('CREATE INDEX idx_venue_blocked_lookup ON venue_blocked_slots(venue_id, date, start_time)');
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_blocked_slots');
    }
};

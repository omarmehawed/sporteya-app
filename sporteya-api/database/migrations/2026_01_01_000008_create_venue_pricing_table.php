<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venue_pricing', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));
            $table->uuid('venue_id')->references('id')->on('venues')->onDelete('cascade');
            $table->uuid('sport_id')->references('id')->on('sports')->onDelete('cascade');
            $table->unsignedTinyInteger('day_of_week'); // 0 = Sunday, ..., 6 = Saturday
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('price_per_hour', 10, 2)->unsigned();
            $table->string('currency', 3)->default('EGP');
            $table->timestamps();
        });

        DB::statement('CREATE INDEX idx_venue_pricing_lookup ON venue_pricing(venue_id, sport_id, day_of_week)');
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_pricing');
    }
};

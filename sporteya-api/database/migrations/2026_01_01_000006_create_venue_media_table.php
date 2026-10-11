<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venue_media', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));
            $table->uuid('venue_id')->references('id')->on('venues')->onDelete('cascade');
            $table->string('url');
            $table->enum('type', ['image', 'video'])->default('image');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        DB::statement('CREATE INDEX idx_venue_media_venue ON venue_media(venue_id, sort_order)');
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_media');
    }
};

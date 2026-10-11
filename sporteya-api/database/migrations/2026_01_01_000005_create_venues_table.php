<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venues', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));
            $table->uuid('owner_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('address');
            $table->string('city');
            $table->string('phone', 20)->nullable();
            $table->boolean('has_lighting')->default(false);
            $table->boolean('has_changing_room')->default(false);
            $table->boolean('has_parking')->default(false);
            $table->boolean('is_active')->default(false); // Correction K: false until admin verifies
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->decimal('rating_avg', 3, 2)->default(0.00);
            $table->unsignedInteger('reviews_count')->default(0);
            $table->timestamps();
        });

        // Correction 5: PostGIS column managed via raw DB::statement()
        DB::statement("ALTER TABLE venues ADD COLUMN location GEOGRAPHY(POINT, 4326) NOT NULL DEFAULT ST_MakePoint(0,0)::geography");
        DB::statement("ALTER TABLE venues ALTER COLUMN location DROP DEFAULT");
        DB::statement("CREATE INDEX idx_venue_location ON venues USING GIST(location)");
        DB::statement("CREATE INDEX idx_venue_owner ON venues(owner_id)");
        DB::statement("CREATE INDEX idx_venue_city ON venues(city)");
        DB::statement("CREATE INDEX idx_venue_status ON venues(is_active, is_verified)");
    }

    public function down(): void
    {
        Schema::dropIfExists('venues');
    }
};

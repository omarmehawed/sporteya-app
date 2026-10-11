<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cancellation_policies', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));
            $table->uuid('venue_id')->unique()->references('id')->on('venues')->onDelete('cascade');
            $table->unsignedInteger('full_refund_hours')->default(24);
            $table->unsignedInteger('partial_refund_hours')->default(12);
            $table->decimal('partial_refund_pct', 5, 2)->default(50.00);
            $table->text('policy_text')->nullable();
            $table->timestamps();
        });

        DB::statement('CREATE INDEX idx_cancellation_policies_venue ON cancellation_policies(venue_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('cancellation_policies');
    }
};

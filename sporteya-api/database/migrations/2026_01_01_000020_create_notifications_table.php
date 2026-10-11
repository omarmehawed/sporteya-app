<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));
            $table->uuid('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('type', 100);
            $table->string('title');
            $table->text('body');
            $table->jsonb('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        // Composite index on (user_id, read_at) for efficient unread count and filtering
        DB::statement('CREATE INDEX idx_notifications_user_read ON notifications(user_id, read_at)');
        DB::statement('CREATE INDEX idx_notifications_type ON notifications(type)');
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};

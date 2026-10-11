<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_results', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));
            $table->uuid('lobby_id')->unique()->references('id')->on('match_lobbies')->onDelete('cascade');
            $table->uuid('recorded_by')->references('id')->on('player_profiles');
            $table->string('score', 50)->nullable();
            $table->string('winner', 100)->nullable();
            $table->jsonb('details')->nullable();
            $table->timestamps();
        });

        DB::statement('CREATE INDEX idx_match_results_lobby ON match_results(lobby_id)');
        DB::statement('CREATE INDEX idx_match_results_recorder ON match_results(recorded_by)');
    }

    public function down(): void
    {
        Schema::dropIfExists('match_results');
    }
};

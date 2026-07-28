<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('player_global_rankings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('window', 10); // GlobalRankingWindow: daily, weekly, alltime
            $table->string('mode', 10); // GlobalRankingMode: hardcore, casual
            $table->unsignedInteger('achievements_unlocked');
            $table->integer('points');
            $table->integer('points_weighted')->default(0);
            $table->unsignedInteger('awards_count')->default(0);
            $table->unsignedInteger('rank_number')->nullable();
            $table->unsignedInteger('weighted_rank_number')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->unique(['window', 'mode', 'user_id'], 'player_global_rankings_user_unique');
            $table->index(['window', 'user_id'], 'player_global_rankings_window_user_index'); // quickly load a user's rankings within a window
            $table->index(['window', 'mode', 'points'], 'player_global_rankings_points_index'); // efficient sorting
            $table->index(['window', 'mode', 'achievements_unlocked'], 'player_global_rankings_unlocks_index'); // efficient sorting
            $table->index(['window', 'mode', 'points_weighted'], 'player_global_rankings_weighted_index'); // efficient sorting
            $table->index(['window', 'mode', 'awards_count'], 'player_global_rankings_awards_index'); // efficient sorting

        });

        // stores precomputed all-time population counts for each rank type.
        // this lets us do a constant-time lookup for counts instead of repeatedly
        // counting the number of ranking rows.
        Schema::create('player_global_ranking_totals', function (Blueprint $table) {
            $table->string('rank_type', 20)->primary(); // RankType: hardcore, casual, retro_points
            $table->unsignedInteger('total');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_global_ranking_totals');
        Schema::dropIfExists('player_global_rankings');
    }
};

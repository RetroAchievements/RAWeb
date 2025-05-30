<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('player_games', function (Blueprint $table) {
            $table->boolean('user_is_tracked')->after('game_id')->nullable()->index();
        });

        Schema::table('player_games', function (Blueprint $table) {
            $table->index(['game_id', 'user_is_tracked', 'achievements_unlocked'], 'idx_game_tracked_unlocked');
            $table->index(['game_id', 'user_is_tracked', 'achievements_unlocked_hardcore'], 'idx_game_tracked_unlocked_hardcore');
        });
    }

    public function down(): void
    {
        Schema::table('player_games', function (Blueprint $table) {
            $table->dropIndex('idx_game_tracked_unlocked_hardcore');
            $table->dropIndex('idx_game_tracked_unlocked');
            $table->dropIndex(['user_is_tracked']);
            $table->dropColumn('user_is_tracked');
        });
    }
};

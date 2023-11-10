<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        // add the inverse indexes for user_id so lookups for untracked and deleted are more likely using indexes

        Schema::table('player_achievements', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound = $sm->listTableIndexes('player_achievements');

            if (!array_key_exists('player_achievements_achievement_id_user_id_unlocked_hardcore_at', $indexesFound)) {
                $table->index(
                    ['achievement_id', 'user_id', 'unlocked_hardcore_at'],
                    'player_achievements_achievement_id_user_id_unlocked_hardcore_at'
                );
            }
        });

        Schema::table('player_games', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound = $sm->listTableIndexes('player_games');

            if (!array_key_exists('player_games_game_id_user_id_index', $indexesFound)) {
                $table->index(['game_id', 'user_id']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('player_achievements', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound = $sm->listTableIndexes('player_achievements');

            if (array_key_exists('player_achievements_achievement_id_user_id_unlocked_hardcore_at', $indexesFound)) {
                $table->dropIndex('player_achievements_achievement_id_user_id_unlocked_hardcore_at');
            }
        });

        Schema::table('player_games', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound = $sm->listTableIndexes('player_games');

            if (array_key_exists('player_games_game_id_user_id_index', $indexesFound)) {
                $table->dropIndex(['game_id', 'user_id']);
            }
        });
    }
};

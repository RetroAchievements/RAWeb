<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('player_games', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound = $sm->listTableIndexes('player_games');

            if (!array_key_exists('player_games_game_id_achievement_set_version_hash_index', $indexesFound)) {
                $table->index(['game_id', 'achievement_set_version_hash']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('player_games', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound = $sm->listTableIndexes('player_games');

            if (array_key_exists('player_games_game_id_achievement_set_version_hash_index', $indexesFound)) {
                $table->dropIndex(['game_id', 'achievement_set_version_hash']);
            }
        });
    }
};

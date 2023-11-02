<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('player_achievements', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound = $sm->listTableIndexes('player_achievements');

            if (!array_key_exists('player_achievements_user_date_achievement', $indexesFound)) {
                $table->index(
                    ['user_id', 'unlocked_at', 'unlocked_hardcore_at', 'achievement_id'],
                    'player_achievements_user_date_achievement'
                );
            }
        });
    }

    public function down(): void
    {
        Schema::table('player_achievements', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound = $sm->listTableIndexes('player_achievements');

            if (array_key_exists('player_achievements_user_date_achievement', $indexesFound)) {
                $table->dropIndex('player_achievements_user_date_achievement');
            }
        });
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        // add the inverse indexes for user_id so lookups for untracked and deleted are more likely using indexes

        Schema::table('Achievements', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound = $sm->listTableIndexes('Achievements');

            if (!array_key_exists('achievements_game_id_published_index', $indexesFound)) {
                $table->index(
                    ['GameID', 'Flags'],
                    'achievements_game_id_published_index'
                );
            }
        });
    }

    public function down(): void
    {
        Schema::table('Achievements', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound = $sm->listTableIndexes('Achievements');

            if (array_key_exists('achievements_game_id_published_index', $indexesFound)) {
                $table->dropIndex('achievements_game_id_published_index');
            }
        });
    }
};

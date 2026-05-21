<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->foreignId('parent_game_id')
                ->nullable()
                ->after('system_id')
                ->constrained('games')
                ->nullOnDelete();
        });

        if (DB::connection()->getDriverName() !== 'sqlite') {
            $this->backfillFromAchievementSetLinks();
            $this->backfillFromSubsetTitles();
        }
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropForeign(['parent_game_id']);
            $table->dropColumn('parent_game_id');
        });
    }

    /**
     * Pass 1: derive parent_game_id from achievement set links. For each game's
     * core set, the earliest other game that uses that set as a non-core type
     * is the parent. Tiebreaker matches the runtime resolver: created_at, id.
     */
    private function backfillFromAchievementSetLinks(): void
    {
        DB::statement(<<<'SQL'
            UPDATE games g
            INNER JOIN (
                SELECT game_id, parent_id
                FROM (
                    SELECT
                        core.game_id,
                        parent_link.game_id AS parent_id,
                        ROW_NUMBER() OVER (
                            PARTITION BY core.game_id
                            ORDER BY parent_link.created_at, parent_link.id
                        ) AS rn
                    FROM game_achievement_sets core
                    INNER JOIN game_achievement_sets parent_link
                        ON parent_link.achievement_set_id = core.achievement_set_id
                        AND parent_link.game_id != core.game_id
                        AND parent_link.type != 'core'
                    WHERE core.type = 'core'
                ) ranked
                WHERE rn = 1
            ) parents ON parents.game_id = g.id
            SET g.parent_game_id = parents.parent_id
        SQL);
    }

    /**
     * Pass 2: for any "[Subset - X]" titled game still unparented, look up the
     * parent by base title + system_id.
     */
    private function backfillFromSubsetTitles(): void
    {
        DB::statement(<<<'SQL'
            UPDATE games subset
            INNER JOIN games parent
                ON parent.system_id = subset.system_id
                AND parent.id != subset.id
                AND parent.deleted_at IS NULL
                AND subset.title LIKE CONCAT(
                   REPLACE(REPLACE(REPLACE(parent.title, '\\', '\\\\'), '%', '\\%'), '_', '\\_'),
                   ' [Subset - %'
               )
            SET subset.parent_game_id = parent.id
            WHERE subset.parent_game_id IS NULL
                AND subset.title LIKE '% [Subset - %'
                AND EXISTS (
                    SELECT 1 FROM game_achievement_sets core_set
                    WHERE core_set.game_id = subset.id
                    AND core_set.type = 'core'
              )
        SQL);
    }
};

<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\Achievement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillAuthorYieldUnlocks extends Command
{
    protected $signature = 'ra:platform:achievement:backfill-author-yield-unlocks';
    protected $description = 'Backfill the author_yield_unlocks column on achievements table';

    private int $chunkSize = 10000;

    public function handle(): void
    {
        $maxId = (int) Achievement::max('id');

        if ($maxId === 0) {
            $this->info('No achievements found.');

            return;
        }

        /**
         * We use a 5-step approach with temporary tables:
         * 1. Count tracked unlocks per achievement (all unlocks minus unranked users).
         * 2. Count tracked author self-unlocks separately.
         * 3. Count tracked maintainer credits (unlocks credited to maintainers).
         * 4. Calculate: tracked_total - self_unlocks - maintainer_credits.
         * 5. Update achievements using an INNER JOIN.
         *
         * For each step that filters unranked users, we use a "count all, then subtract
         * unranked" approach. This is fast because:
         * 1. Counting all unlocks requires no joins with external tables.
         * 2. Counting unranked unlocks starts from unranked_users (tiny table, ~200 rows),
         *    which efficiently drives the join to player_achievements via index.
         *
         * The alternative (LEFT JOIN unranked_users on every player_achievements row)
         * would scan the entire 500M+ row table, which is brutally slow and takes hours.
         */
        $this->info('Step 1/5: Counting all unlocks...');
        $this->countAllUnlocks($maxId);

        $this->info('Step 2/5: Counting self-unlocks...');
        $this->countSelfUnlocks();

        $this->info('Step 3/5: Counting maintainer credits...');
        $this->countMaintainerCredits();

        $this->info('Step 4/5: Calculating final counts...');
        $this->calculateFinalCounts();

        $this->info('Step 5/5: Updating achievements...');
        $this->updateAchievements();

        $this->cleanup();

        $this->newLine();
        $this->info('Backfill complete.');
    }

    private function countAllUnlocks(int $maxId): void
    {
        $totalBatches = (int) ceil($maxId / $this->chunkSize);

        // First, count ALL unlocks (no joins, fast).
        DB::statement('CREATE TEMPORARY TABLE tmp_all_counts (achievement_id BIGINT UNSIGNED PRIMARY KEY, cnt INT UNSIGNED)');

        $progressBar = $this->output->createProgressBar($totalBatches);
        $progressBar->start();

        $currentId = 1;
        while ($currentId <= $maxId) {
            $endId = min($currentId + $this->chunkSize - 1, $maxId);

            DB::statement(<<<SQL
                INSERT INTO tmp_all_counts
                SELECT pa.achievement_id, COUNT(*)
                FROM player_achievements pa
                WHERE pa.achievement_id BETWEEN ? AND ?
                GROUP BY pa.achievement_id
            SQL, [$currentId, $endId]);

            $progressBar->advance();
            $currentId = $endId + 1;
        }

        $progressBar->finish();
        $this->newLine();

        // Next, count untracked unlocks by starting from unranked_users.
        DB::statement(<<<SQL
            CREATE TEMPORARY TABLE tmp_unranked_counts AS
            SELECT pa.achievement_id, COUNT(*) as cnt
            FROM unranked_users uu
            INNER JOIN player_achievements pa ON pa.user_id = uu.user_id
            GROUP BY pa.achievement_id
        SQL);
        DB::statement('ALTER TABLE tmp_unranked_counts ADD INDEX (achievement_id)');

        // Finally, calculate tracked counts (all - unranked).
        DB::statement('CREATE TEMPORARY TABLE tmp_total_counts (achievement_id BIGINT UNSIGNED PRIMARY KEY, cnt INT UNSIGNED)');
        DB::statement(<<<SQL
            INSERT INTO tmp_total_counts
            SELECT
                a.achievement_id,
                a.cnt - COALESCE(u.cnt, 0)
            FROM tmp_all_counts a
            LEFT JOIN tmp_unranked_counts u ON u.achievement_id = a.achievement_id
        SQL);

        DB::statement('DROP TEMPORARY TABLE tmp_all_counts');
        DB::statement('DROP TEMPORARY TABLE tmp_unranked_counts');
    }

    private function countSelfUnlocks(): void
    {
        DB::statement(<<<SQL
            CREATE TEMPORARY TABLE tmp_self_unlock_counts AS
            SELECT pa.achievement_id, COUNT(*) as cnt
            FROM player_achievements pa
            INNER JOIN achievements a ON a.id = pa.achievement_id
            WHERE pa.user_id = a.user_id
            GROUP BY pa.achievement_id
        SQL);
        DB::statement('ALTER TABLE tmp_self_unlock_counts ADD INDEX (achievement_id)');
    }

    private function countMaintainerCredits(): void
    {
        // First, count all maintainer credits.
        DB::statement(<<<SQL
            CREATE TEMPORARY TABLE tmp_all_maintainer AS
            SELECT amu.achievement_id, COUNT(*) as cnt
            FROM achievement_maintainer_unlocks amu
            GROUP BY amu.achievement_id
        SQL);
        DB::statement('ALTER TABLE tmp_all_maintainer ADD INDEX (achievement_id)');

        // Then, count unranked maintainer credits.
        DB::statement(<<<SQL
            CREATE TEMPORARY TABLE tmp_unranked_maintainer AS
            SELECT amu.achievement_id, COUNT(*) as cnt
            FROM unranked_users uu
            INNER JOIN player_achievements pa ON pa.user_id = uu.user_id
            INNER JOIN achievement_maintainer_unlocks amu ON amu.player_achievement_id = pa.id
            GROUP BY amu.achievement_id
        SQL);
        DB::statement('ALTER TABLE tmp_unranked_maintainer ADD INDEX (achievement_id)');

        // Finally, calculate tracked maintainer credits (all - unranked).
        DB::statement(<<<SQL
            CREATE TEMPORARY TABLE tmp_maintainer_counts AS
            SELECT
                a.achievement_id,
                a.cnt - COALESCE(u.cnt, 0) as cnt
            FROM tmp_all_maintainer a
            LEFT JOIN tmp_unranked_maintainer u ON u.achievement_id = a.achievement_id
        SQL);
        DB::statement('ALTER TABLE tmp_maintainer_counts ADD INDEX (achievement_id)');

        DB::statement('DROP TEMPORARY TABLE tmp_all_maintainer');
        DB::statement('DROP TEMPORARY TABLE tmp_unranked_maintainer');
    }

    private function calculateFinalCounts(): void
    {
        DB::statement(<<<SQL
            CREATE TEMPORARY TABLE tmp_final_counts AS
            SELECT
                t.achievement_id,
                t.cnt - COALESCE(s.cnt, 0) - COALESCE(m.cnt, 0) as cnt
            FROM tmp_total_counts t
            LEFT JOIN tmp_self_unlock_counts s ON s.achievement_id = t.achievement_id
            LEFT JOIN tmp_maintainer_counts m ON m.achievement_id = t.achievement_id
        SQL);

        DB::statement('ALTER TABLE tmp_final_counts ADD INDEX (achievement_id)');
    }

    private function updateAchievements(): void
    {
        $rowCount = (int) DB::table('tmp_final_counts')->count();
        $updateBatches = (int) ceil($rowCount / $this->chunkSize);

        $progressBar = $this->output->createProgressBar($updateBatches);
        $progressBar->start();

        $offset = 0;
        while ($offset < $rowCount) {
            DB::statement(<<<SQL
                UPDATE achievements a
                INNER JOIN (
                    SELECT achievement_id, cnt FROM tmp_final_counts LIMIT ? OFFSET ?
                ) c ON c.achievement_id = a.id
                SET a.author_yield_unlocks = c.cnt
            SQL, [$this->chunkSize, $offset]);

            $progressBar->advance();
            $offset += $this->chunkSize;
        }

        $progressBar->finish();
    }

    private function cleanup(): void
    {
        DB::statement('DROP TEMPORARY TABLE tmp_total_counts');
        DB::statement('DROP TEMPORARY TABLE tmp_self_unlock_counts');
        DB::statement('DROP TEMPORARY TABLE tmp_maintainer_counts');
        DB::statement('DROP TEMPORARY TABLE tmp_final_counts');
    }
}

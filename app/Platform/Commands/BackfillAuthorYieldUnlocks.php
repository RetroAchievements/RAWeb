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
         * A naive backfill approach would be a single UPDATE with a correlated subquery:
         *   UPDATE achievements SET author_yield_unlocks = (
         *     SELECT COUNT(*) FROM player_achievements pa
         *     WHERE pa.achievement_id = a.id
         *       AND pa.user_id != a.user_id
         *       AND NOT EXISTS (SELECT 1 FROM achievement_maintainer_unlocks amu WHERE amu.player_achievement_id = pa.id)
         *   )
         *
         * This is extremely slow because:
         * 1. The NOT EXISTS subquery runs for every row in player_achievements (~500M rows).
         * 2. Even with batching, each batch takes 2+ minutes due to the correlated subquery.
         *
         * Instead, we use a 5-step approach with temporary tables:
         * 1. Count ALL unlocks per achievement (fast - simple GROUP BY with no JOINs).
         * 2. Count author self-unlocks separately.
         * 3. Count maintainer credits (unlocks credited to maintainers, not authors).
         * 4. Calculate: total - self_unlocks - maintainer_credits.
         * 5. Update achievements using an INNER JOIN.
         *
         * This separates the slow JOIN from the hot path and processes each concern once
         * instead of per-batch, reducing total runtime from hours to minutes.
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

        DB::statement('CREATE TEMPORARY TABLE tmp_total_counts (achievement_id BIGINT UNSIGNED PRIMARY KEY, cnt INT UNSIGNED)');

        $progressBar = $this->output->createProgressBar($totalBatches);
        $progressBar->start();

        $currentId = 1;
        while ($currentId <= $maxId) {
            $endId = min($currentId + $this->chunkSize - 1, $maxId);

            DB::statement(<<<SQL
                INSERT INTO tmp_total_counts
                SELECT achievement_id, COUNT(*)
                FROM player_achievements
                WHERE achievement_id BETWEEN ? AND ?
                GROUP BY achievement_id
            SQL, [$currentId, $endId]);

            $progressBar->advance();
            $currentId = $endId + 1;
        }

        $progressBar->finish();
        $this->newLine();
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
        DB::statement(<<<SQL
            CREATE TEMPORARY TABLE tmp_maintainer_counts AS
            SELECT achievement_id, COUNT(*) as cnt
            FROM achievement_maintainer_unlocks
            GROUP BY achievement_id
        SQL);

        DB::statement('ALTER TABLE tmp_maintainer_counts ADD INDEX (achievement_id)');
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

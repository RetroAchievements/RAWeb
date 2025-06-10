<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Community\Enums\Rank;
use App\Models\System;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CrawlPlayerWeightedPoints extends Command
{
    protected $signature = 'ra:platform:player:crawl-weighted-points 
                            {--batch=100 : Number of users to process per run} 
                            {--user= : Update a specific user ID}
                            {--reset : Reset the crawler to start from the beginning}';
    protected $description = 'Crawl through users to incrementally update weighted points';

    private const CACHE_KEY = 'weighted_points_crawler_last_user_id';
    private const CACHE_TTL = 60 * 60 * 24 * 90; // 90 days

    private int $numUpdatedUsers = 0;
    private int $numProcessedPlayerGames = 0;

    public function handle(): void
    {
        $batchSize = (int) $this->option('batch');
        $specificUserId = $this->option('user');

        if ($this->option('reset')) {
            Cache::forget(self::CACHE_KEY);
            $this->info('Crawler reset. The crawler will start from the beginning on the next run.');

            return;
        }

        if ($specificUserId) {
            $this->updateSingleUser((int) $specificUserId);

            return;
        }

        $this->processBatch($batchSize);
    }

    private function processBatch(int $batchSize): void
    {
        $lastProcessedUserId = Cache::get(self::CACHE_KEY, 0);

        $this->info("Processing batch of {$batchSize} users...");
        $this->info("Starting after user ID: {$lastProcessedUserId}.");

        // Get the next batch of users to be processed.
        $users = User::query()
            ->where('ID', '>', $lastProcessedUserId)
            ->where('TrueRAPoints', '>=', Rank::MIN_TRUE_POINTS) // this filters out hundreds of thousands of users
            ->whereNull('unranked_at')
            ->orderBy('ID')
            ->limit($batchSize)
            ->get();

        if ($users->isEmpty()) {
            $this->info('No more users to process. The crawler has completed full cycle.');
            Cache::forget(self::CACHE_KEY);

            return;
        }

        $lastUserId = 0;
        foreach ($users as $user) {
            $this->updateUserWeightedPoints($user);
            $lastUserId = $user->id;

            unset($user); // use as little memory as possible ... free up memory after each user
        }

        // Save the crawler's current progress.
        Cache::put(self::CACHE_KEY, $lastUserId, self::CACHE_TTL);

        $this->info("Batch completed. Processed up to user ID: {$lastUserId}.");
        $this->info("Updated {$this->numUpdatedUsers} users' TrueRAPoints values.");
        $this->info("Processed {$this->numProcessedPlayerGames} player_games records.");

        // Check if there are any more users to process.
        $remainingCount = User::query()
            ->where('ID', '>', $lastUserId)
            ->where('TrueRAPoints', '>=', Rank::MIN_TRUE_POINTS)
            ->whereNull('unranked_at')
            ->count();

        if ($remainingCount > 0) {
            $this->info("Approximately {$remainingCount} users remaining to process.");
        }
    }

    private function updateSingleUser(int $userId): void
    {
        $user = User::find($userId);
        if (!$user) {
            $this->error("User with ID {$userId} not found.");

            return;
        }

        $this->info("Updating user [{$user->id}:{$user->display_name}].");
        $this->info("Current weighted points: {$user->TrueRAPoints}.");

        $this->updateUserWeightedPoints($user);

        if ($this->numUpdatedUsers > 0) {
            $user->refresh();
            $this->info("New weighted points: {$user->TrueRAPoints}.");
        }

        $this->info("Done.");
    }

    private function updateUserWeightedPoints(User $user): void
    {
        // Update all player_games.points_weighted values in a single query.
        // This calculates the sum of TrueRatio for all hardcore achievements per game.
        $updatedRows = DB::update(<<<SQL
            UPDATE player_games pg
            LEFT JOIN (
                SELECT
                    pa.user_id,
                    ach.GameID as game_id,
                    SUM(ach.TrueRatio) as weighted_points
                FROM player_achievements pa
                INNER JOIN Achievements ach ON ach.ID = pa.achievement_id
                WHERE
                    pa.user_id = ?
                    AND pa.unlocked_hardcore_at IS NOT NULL
                GROUP BY pa.user_id, ach.GameID
            ) AS calculated ON pg.user_id = calculated.user_id AND pg.game_id = calculated.game_id
            SET pg.points_weighted = COALESCE(calculated.weighted_points, 0)
            WHERE
                pg.user_id = ?
        SQL, [$user->id, $user->id]);

        if ($updatedRows > 0) {
            $this->numProcessedPlayerGames += $updatedRows;
        }

        // Now update the user's total TrueRAPoints by summing all player_games.points_weighted.
        // This follows the same pattern as UpdatePlayerMetricsAction.
        $totalWeightedPoints = DB::table('player_games')
            ->join('GameData', 'GameData.ID', '=', 'player_games.game_id')
            ->whereNotIn('GameData.ConsoleID', [System::Events, System::Hubs])
            ->where('player_games.user_id', $user->id)
            ->where('player_games.achievements_unlocked', '>', 0)
            ->sum('player_games.points_weighted');

        // ->sum() returns a mixed type. Force it to be an integer.
        $totalWeightedPoints = (int) $totalWeightedPoints;

        // Only update if the value has changed.
        if ($user->TrueRAPoints !== $totalWeightedPoints) {
            $user->TrueRAPoints = $totalWeightedPoints;
            $user->saveQuietly();

            $this->numUpdatedUsers++;
        }
    }
}

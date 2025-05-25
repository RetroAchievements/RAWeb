<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Community\Enums\Rank;
use App\Models\PlayerAchievementSet;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CrawlPlayerWeightedPoints extends Command
{
    protected $signature = 'ra:platform:player:crawl-weighted-points 
                            {--batch=20 : Number of users to process per run} 
                            {--user= : Update a specific user ID}
                            {--reset : Reset the crawler to start from the beginning}';
    protected $description = 'Crawl through users to incrementally update weighted points for users and their achievement sets';

    private const CACHE_KEY = 'weighted_points_crawler_last_user_id';
    private const CACHE_TTL = 60 * 60 * 24 * 90; // 90 days

    private int $numUpdatedUsers = 0;
    private int $numUpdatedPlayerSets = 0;

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
            $this->updateUserAndAchievementSets($user);
            $lastUserId = $user->id;

            unset($user); // use as little memory as possible ... free up memory after each user
        }

        // Save the crawler's current progress.
        Cache::put(self::CACHE_KEY, $lastUserId, self::CACHE_TTL);

        $this->info("Batch completed. Processed up to user ID: {$lastUserId}.");
        $this->info("Updated {$this->numUpdatedUsers} users' weighted points.");
        $this->info("Updated {$this->numUpdatedPlayerSets} player achievement sets.");

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

        $this->updateUserAndAchievementSets($user);

        if ($this->numUpdatedUsers > 0) {
            $user->refresh();
            $this->info("New weighted points: {$user->TrueRAPoints}.");
        }

        $this->info("Updated {$this->numUpdatedUsers} users.");
        $this->info("Updated {$this->numUpdatedPlayerSets} player achievement sets.");
    }

    private function updateUserAndAchievementSets(User $user): void
    {
        $this->updateUserWeightedPoints($user);
        $this->updateUserAchievementSets($user);
    }

    private function updateUserWeightedPoints(User $user): void
    {
        // Calculate the sum of weighted points for all hardcore achievements unlocked by the user.
        // Use a direct query to avoid loading all achievements into memory.
        $weightedPointsSum = $user->playerAchievements()
            ->join('Achievements', 'player_achievements.achievement_id', '=', 'Achievements.ID')
            ->whereNotNull('player_achievements.unlocked_hardcore_at')
            ->sum('Achievements.TrueRatio');

        $weightedPointsSum = (int) $weightedPointsSum;

        // Only update if the value has changed.
        if ($user->TrueRAPoints !== $weightedPointsSum) {
            $user->TrueRAPoints = $weightedPointsSum;
            $user->saveQuietly();

            $this->numUpdatedUsers++;
        }
    }

    private function updateUserAchievementSets(User $user): void
    {
        // Process each player achievement set one at a time to minimize memory usage.
        PlayerAchievementSet::where('user_id', $user->id)
            ->chunkById(10, function ($playerAchievementSets) use ($user) {
                /** @var PlayerAchievementSet $playerSet */
                foreach ($playerAchievementSets as $playerSet) {
                    // Get achievement IDs for this set without loading all achievement models.
                    $achievementIds = DB::table('achievement_set_achievements')
                        ->where('achievement_set_id', $playerSet->achievement_set_id)
                        ->pluck('achievement_id');

                    if ($achievementIds->isEmpty()) {
                        continue;
                    }

                    // Calculate the sum using a direct query to minimize memory usage.
                    $setWeightedPoints = $user->playerAchievements()
                        ->join('Achievements', 'player_achievements.achievement_id', '=', 'Achievements.ID')
                        ->whereIn('player_achievements.achievement_id', $achievementIds)
                        ->whereNotNull('player_achievements.unlocked_hardcore_at')
                        ->sum('Achievements.TrueRatio');

                    $setWeightedPoints = (int) $setWeightedPoints;

                    // Only update if the value has changed.
                    if ($playerSet->points_weighted !== $setWeightedPoints) {
                        $playerSet->points_weighted = $setWeightedPoints;
                        $playerSet->saveQuietly();

                        $this->numUpdatedPlayerSets++;
                    }
                }
            });
    }
}

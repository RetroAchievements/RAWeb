<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\User;
use App\Platform\Actions\UpdatePlayerPointsStats as UpdatePlayerPointsStatsAction;
use App\Platform\Enums\PlayerStatType;
use App\Platform\Jobs\UpdatePlayerPointsStatsJob;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;

class UpdatePlayerPointsStats extends Command
{
    protected $signature = 'ra:platform:player:update-points-stats
                            {userId? : User ID or username. Usernames containing only numbers are ambiguous and must be referenced by user ID}
                            {--date= : A fake date in the format "YYYY-MM-DD" to test against}
                            {--existing-only : Only run for existing points stats records}';
    protected $description = 'Update player points stats';

    public function __construct(
        private readonly UpdatePlayerPointsStatsAction $updatePlayerPointsStats
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $userId = $this->argument('userId');
        $mockCurrentDate = $this->option('date');

        if ($mockCurrentDate) {
            $this->info("Using hardcoded current date of {$mockCurrentDate}.");
            Carbon::setTestNow(Carbon::parse($mockCurrentDate));
        }

        if ($userId !== null) {
            $user = is_numeric($userId)
                ? User::findOrFail($userId)
                : User::where('User', $userId)->firstOrFail();

            $this->info("Updating points stats for player [{$user->id}:{$user->username}]");

            $this->updatePlayerPointsStats->execute($user);
        } else {
            // We want to dispatch unique jobs for all user IDs that have had activity
            // over the last 32 days or have rows in `player_stats` with one of the
            // points types.
            $relevantPlayerStatTypes = [
                PlayerStatType::PointsHardcoreDay,
                PlayerStatType::PointsHardcoreWeek,
                PlayerStatType::PointsSoftcoreDay,
                PlayerStatType::PointsSoftcoreWeek,
                PlayerStatType::PointsWeightedDay,
                PlayerStatType::PointsWeightedWeek,
            ];

            $baseUserQuery = User::query();

            if (!$this->option('existing-only')) {
                $baseUserQuery = $baseUserQuery->whereHas('playerAchievements', function ($query) {
                    $query->whereBetween('unlocked_at', [Carbon::now()->subDays(8), Carbon::now()]);
                });
            }

            $baseUserQuery = $baseUserQuery->orWhereHas('playerStats', function ($query) use ($relevantPlayerStatTypes) {
                $query->whereIn('type', $relevantPlayerStatTypes);
            });

            $distinctUserCount = $baseUserQuery->count();
            $this->info("Preparing batch jobs to update points player stats for {$distinctUserCount} users.");

            $progressBar = $this->output->createProgressBar($distinctUserCount);
            $progressBar->start();

            // Retrieve user IDs in chunks and create jobs.
            $baseUserQuery->chunk(100, function ($users) use ($progressBar, $mockCurrentDate) {
                $jobs = $users->map(function ($user) use ($mockCurrentDate) {
                    return new UpdatePlayerPointsStatsJob($user->id, $mockCurrentDate);
                })->all();

                // Dispatch jobs for the current chunk.
                Bus::batch($jobs)->onQueue('player-points-stats')->dispatch();

                $progressBar->advance(count($users));
            });

            $progressBar->finish();

            $this->info('All jobs have been dispatched.');
        }
    }
}

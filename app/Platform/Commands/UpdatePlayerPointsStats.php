<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\User;
use App\Platform\Actions\UpdatePlayerPointsStatsAction;
use App\Platform\Enums\PlayerStatType;
use App\Platform\Jobs\UpdatePlayerPointsStatsBatchJob;
use Illuminate\Bus\BatchRepository;
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
                : User::whereName($userId)->firstOrFail();

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

            $baseUserQuery = $baseUserQuery->orWhere(function ($query) use ($relevantPlayerStatTypes) {
                $query->whereHas('playerStats', function ($subQuery) use ($relevantPlayerStatTypes) {
                    $subQuery->whereIn('type', $relevantPlayerStatTypes);
                })->where('LastLogin', '>=', Carbon::now()->subDays(32));
            });

            $distinctUserCount = $baseUserQuery->count();
            $this->info("Preparing batch jobs to update points player stats for {$distinctUserCount} users.");

            $progressBar = $this->output->createProgressBar($distinctUserCount);
            $progressBar->start();

            // Retrieve ALL user IDs first (more efficient).
            $this->info('Collecting user IDs...');
            $allUserIds = $baseUserQuery->pluck('ID')->toArray();

            // Now create batch jobs from the user IDs.
            $this->info('Creating batch jobs...');
            $jobs = [];
            $chunks = array_chunk($allUserIds, 200);

            foreach ($chunks as $userIdChunk) {
                $jobs[] = new UpdatePlayerPointsStatsBatchJob(
                    $userIdChunk,
                    $mockCurrentDate
                );
                $progressBar->advance(count($userIdChunk));
            }

            // Dispatch all jobs as a batch.
            if (!empty($jobs)) {
                Bus::batch($jobs)
                    ->name('player-points-stats-batch')
                    ->onQueue('player-points-stats-batch')
                    ->allowFailures()
                    ->finally(function ($batch) {
                        // mark batch as finished even if jobs failed
                        if (!$batch->finished()) {
                            resolve(BatchRepository::class)->markAsFinished($batch->id);
                        }
                    })
                    ->dispatch();
            }

            $progressBar->finish();

            $this->info('All jobs have been dispatched.');
        }
    }
}

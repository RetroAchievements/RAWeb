<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\User;
use App\Platform\Actions\UpdatePlayerBeatenGamesStats as UpdatePlayerBeatenGamesStatsAction;
use App\Platform\Jobs\UpdatePlayerBeatenGamesStatsJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;

class UpdatePlayerBeatenGamesStats extends Command
{
    protected $signature = 'ra:platform:player:update-beaten-games-stats
                            {userId? : User ID or username. Usernames containing only numbers are ambiguous and must be referenced by user ID}';
    protected $description = 'Update player beaten games stats';

    public function __construct(
        private readonly UpdatePlayerBeatenGamesStatsAction $updatePlayerBeatenGamesStats
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $userId = $this->argument('userId');

        if ($userId !== null) {
            $user = is_numeric($userId)
                ? User::findOrFail($userId)
                : User::where('User', $userId)->firstOrFail();

            $this->info('Updating stats for player [' . $user->id . ':' . $user->username . ']');

            $this->updatePlayerBeatenGamesStats->execute($user);
        } else {
            // We want to dispatch unique jobs for all user IDs that are
            // present on the player_games table.
            $baseUserQuery = User::whereExists(function ($query) {
                $query->select(DB::raw(1))
                      ->from('player_games')
                      ->whereRaw('player_games.user_id = UserAccounts.ID');
            });

            $distinctUserCount = $baseUserQuery->count();
            $this->info('Preparing batch jobs to update player stats for ' . $distinctUserCount . ' users.');

            $progressBar = $this->output->createProgressBar($distinctUserCount);
            $progressBar->start();

            // Retrieve user IDs in chunks and create jobs.
            $baseUserQuery->chunkById(100, function ($users) use ($progressBar) {
                $jobs = $users->map(function ($user) {
                    return (new UpdatePlayerBeatenGamesStatsJob($user->id))->onQueue('player-beaten-games-stats');
                })->all();

                // Dispatch jobs for the current chunk.
                Bus::batch($jobs)->onQueue('player-beaten-games-stats')->dispatch();

                $progressBar->advance(count($users));
            });

            $progressBar->finish();

            $this->info("All jobs have been dispatched.");
        }
    }
}

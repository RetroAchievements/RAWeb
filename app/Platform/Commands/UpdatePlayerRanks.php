<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Platform\Actions\UpdatePlayerRanks as UpdatePlayerRanksAction;
use App\Platform\Jobs\UpdatePlayerRanksJob;
use App\Site\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;

class UpdatePlayerRanks extends Command
{
    protected $signature = 'ra:platform:player:update-ranks
                            {userId? : User ID or username. Usernames containing only numbers are ambiguous and must be referenced by user ID}';
    protected $description = 'Update player global leaderboard ranks';

    public function __construct(
        private readonly UpdatePlayerRanksAction $updatePlayerRanks
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

            $this->info('Updating rankings for player [' . $user->id . ':' . $user->username . ']');

            $this->updatePlayerRanks->execute($user);
        } else {
            // We want to dispatch unique jobs for all user IDs that are
            // present on the player_games table.
            $baseUserQuery = User::whereExists(function ($query) {
                $query->select(DB::raw(1))
                      ->from('player_games')
                      ->whereRaw('player_games.user_id = UserAccounts.ID');
            });

            $distinctUserCount = $baseUserQuery->count();
            $this->info('Preparing batch jobs to update player ranks for ' . $distinctUserCount . ' users.');

            $progressBar = $this->output->createProgressBar($distinctUserCount);
            $progressBar->start();

            // Retrieve user IDs in chunks and create jobs.
            $baseUserQuery->chunkById(100, function ($users) use ($progressBar) {
                $jobs = $users->map(function ($user) {
                    return (new UpdatePlayerRanksJob($user->id))->onQueue('player-ranks');
                })->all();

                // Dispatch jobs for the current chunk.
                Bus::batch($jobs)->onQueue('player-ranks')->dispatch();

                $progressBar->advance(count($users));
            });

            $progressBar->finish();

            $this->info("All jobs have been dispatched.");
        }
    }
}

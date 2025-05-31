<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncPlayerGamesTrackedStatus extends Command
{
    protected $signature = 'ra:sync:player-games-tracked-status';
    protected $description = 'Sync user_is_tracked field for all player_games records based on UserAccounts.unranked_at and Deleted status';

    public function handle(): void
    {
        $this->info('Starting sync of player_games tracked status...');

        $total = DB::table('player_games')->count();
        $this->info("Found {$total} player_games records to process.");

        if ($total === 0) {
            $this->info('No records to process.');

            return;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        // Use the DB facade for performance reasons.
        DB::table('player_games')->orderBy('id')->chunk(5000, function ($playerGames) use ($bar) {
            // Collect all user IDs from this chunk.
            $userIds = $playerGames->pluck('user_id')->unique();

            // Get all user tracked statuses in one query.
            // Use the DB facade for performance reasons.
            $users = DB::table('UserAccounts')
                ->whereIn('ID', $userIds)
                ->select('ID', 'unranked_at', 'Deleted')
                ->get()
                ->keyBy('ID');

            // Build batch update data.
            $trackedIds = [];
            $untrackedIds = [];

            foreach ($playerGames as $playerGame) {
                if (isset($users[$playerGame->user_id])) {
                    $user = $users[$playerGame->user_id];
                    $isTracked = $user->unranked_at === null && $user->Deleted === null;

                    if ($isTracked) {
                        $trackedIds[] = $playerGame->id;
                    } else {
                        $untrackedIds[] = $playerGame->id;
                    }
                }
                $bar->advance();
            }

            // Batch update tracked records.
            // Use the DB facade for performance reasons.
            if (!empty($trackedIds)) {
                DB::table('player_games')
                    ->whereIn('id', $trackedIds)
                    ->update(['user_is_tracked' => 1]);
            }

            // Batch update untracked records.
            // Use the DB facade for performance reasons.
            if (!empty($untrackedIds)) {
                DB::table('player_games')
                    ->whereIn('id', $untrackedIds)
                    ->update(['user_is_tracked' => 0]);
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->info('Done syncing player_games tracked status.');
    }
}

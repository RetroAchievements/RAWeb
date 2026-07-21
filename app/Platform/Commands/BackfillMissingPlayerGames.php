<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\Game;
use App\Models\PlayerAchievement;
use App\Models\User;
use App\Platform\Actions\AttachPlayerGameAction;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class BackfillMissingPlayerGames extends Command
{
    protected $signature = 'ra:platform:game:backfill-missing-player-games
                            {gameIds : Comma-separated list of game IDs}';
    protected $description = 'Create missing player_games rows for users who have unlocks on a game';

    public function handle(AttachPlayerGameAction $attachPlayerGame): void
    {
        $gameIds = collect(explode(',', $this->argument('gameIds')))
            ->filter()
            ->map(fn ($id) => (int) $id);

        // Unlocks follow achievements when they're moved between games. The migration tool
        // backfills player_games rows for affected users, but moves performed before the
        // tool existed (Nov 2023) didn't do this. Any user+game pair with an unlock and no
        // player_games row inflates unlock counts relative to player counts, with nothing
        // downstream to ever heal it.
        $orphanedPairs = PlayerAchievement::query()
            ->join('achievements', 'achievements.id', '=', 'player_achievements.achievement_id')
            ->leftJoin('player_games', function ($join) {
                $join->on('player_games.user_id', '=', 'player_achievements.user_id')
                    ->on('player_games.game_id', '=', 'achievements.game_id');
            })
            ->whereNull('player_games.id')
            ->whereIn('achievements.game_id', $gameIds)
            ->distinct()
            ->get(['player_achievements.user_id', 'achievements.game_id']);

        $this->info('Backfilling ' . $orphanedPairs->count() . ' missing player_games ' . Str::plural('row', $orphanedPairs->count()) . '.');

        foreach ($orphanedPairs->groupBy('game_id') as $gameId => $pairs) {
            $game = Game::find($gameId);
            if (!$game) {
                continue;
            }

            // PlayerGameAttached cascades into UpdatePlayerGameMetricsJob, which
            // recalculates the row's metrics and the game's player counts for us.
            foreach (User::findMany($pairs->pluck('user_id')) as $user) {
                $attachPlayerGame->execute($user, $game);
            }
        }
    }
}

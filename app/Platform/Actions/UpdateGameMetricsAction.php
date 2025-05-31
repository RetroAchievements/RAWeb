<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;
use App\Platform\Events\GameMetricsUpdated;
use App\Platform\Jobs\UpdateGamePlayerGamesJob;
use Illuminate\Support\Facades\Log;

class UpdateGameMetricsAction
{
    public function execute(Game $game): void
    {
        $game->achievements_published = $game->achievements()->published()->count();
        $game->achievements_unpublished = $game->achievements()->unpublished()->count();

        // update achievements version by changed hash
        // if ($game->attributes['achievements_total']) {
        //     $game->attributes['achievements_version_hash'] = md5($publishedAchievements->implode('trigger'));
        //     if ($game->isDirty('achievements_version_hash')) {
        //         $game->attributes['achievements_version'] = $game->achievements_version + 1;
        //     }
        // }

        $game->points_total = $game->achievements()->published()->sum('points');
        // NOTE $game->TotalTruePoints are updated separately

        $parentGame = $game->parentGame();
        if ($parentGame) {
            // NOTE: This assumes everyone who plays a child set also plays the parent set.
            //       These counts should technically be the union of users from both sets.
            if ($parentGame->players_total > 0) {
                $game->players_total = $parentGame->players_total;
                $game->players_hardcore = $parentGame->players_hardcore;
            } else {
                $parentGame = null;
            }
        }

        if (!$parentGame) {
            $game->players_total = $game->playerGames()
                ->where('achievements_unlocked', '>', 0)
                ->whereHas('user', function ($query) { $query->tracked(); })
                ->count();
            $game->players_hardcore = $game->playerGames()
                ->where('achievements_unlocked_hardcore', '>', 0)
                ->whereHas('user', function ($query) { $query->tracked(); })
                ->count();
        }

        $achievementSetVersionChanged = false;
        $achievementsPublishedChange = 0;
        $pointsTotalChange = 0;
        $playersTotalChange = 0;
        $playersHardcoreChange = 0;
        if ($game->achievements_published || $game->achievements_unpublished) {
            $versionHashFields = ['ID', 'MemAddr', 'type', 'Points'];
            $achievementSetVersionHashPayload = $game->achievements()->published()
                ->orderBy('ID')
                ->get($versionHashFields)
                ->map(fn ($achievement) => implode('-', $achievement->only($versionHashFields)))
                ->implode('-');
            $game->achievement_set_version_hash = hash('sha256', $achievementSetVersionHashPayload);

            $achievementSetVersionChanged = $game->isDirty('achievement_set_version_hash');
            $achievementsPublishedChange = $game->achievements_published - $game->getOriginal('achievements_published');
            $pointsTotalChange = $game->points_total - $game->getOriginal('points_total');
            $playersTotalChange = $game->players_total - $game->getOriginal('players_total');
            $playersHardcoreChange = $game->players_hardcore - $game->getOriginal('players_hardcore');
        }
        $pointsWeightedBeforeUpdate = $game->TotalTruePoints;

        $game->saveQuietly();

        app()->make(UpdateGameAchievementsMetricsAction::class)
            ->execute($game);

        app()->make(UpsertGameCoreAchievementSetFromLegacyFlagsAction::class)
            ->execute($game);

        $game->refresh();

        $pointsWeightedChange = $game->TotalTruePoints - $pointsWeightedBeforeUpdate;

        GameMetricsUpdated::dispatch($game);

        // TODO dispatch events for achievement set and game metrics changes
        $tmp = $achievementsPublishedChange;
        $tmp = $pointsTotalChange;
        $tmp = $pointsWeightedChange;
        $tmp = $playersTotalChange;
        $tmp = $playersHardcoreChange;

        if ($achievementSetVersionChanged) {
            Log::info("Hash change detected for game [" . $game->id . "]. Queueing all outdated player games.");
            dispatch(new UpdateGamePlayerGamesJob($game->id))
                ->onQueue('game-player-games');
        }
    }
}

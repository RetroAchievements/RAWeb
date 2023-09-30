<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Platform\Events\GameMetricsUpdated;
use App\Platform\Models\Game;

class UpdateGameMetrics
{
    public function execute(Game $game): void
    {
        // TODO aggregate achievement sets' metrics as soon as those have been introduced

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

        // TODO switch as soon as all player_games have been populated
        //     $game->players_total = $game->playerGames()
        //         ->leftJoin('UserAccounts as user', 'user.ID', '=', 'player_games.user_id')
        //         ->where('player_games.achievements_unlocked', '>', 0)
        //         ->where('user.Untracked', false)
        //         ->count();
        //     $game->players_hardcore = $game->playerGames()
        //         ->leftJoin('UserAccounts as user', 'user.ID', '=', 'player_games.user_id')
        //         ->where('player_games.achievements_unlocked_hardcore', '>', 0)
        //         ->where('user.Untracked', false)
        //         ->count();
        $parentGameId = getParentGameIdFromGameId($game->id);
        $game->players_total = getTotalUniquePlayers($game->id, $parentGameId);
        $game->players_hardcore = getTotalUniquePlayers($game->id, $parentGameId, null, true);

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

        $game->save();

        // dispatch(new UpdateGameAchievementsMetricsJob($game->id))->onQueue('game-metrics');
        app()->make(UpdateGameAchievementsMetrics::class)
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

        // ad-hoc updates for player games, so they can be updated the next time a player updates their profile
        // Note that those might be multiple thousand entries depending on a game's players count

        $updateReason = null;
        if ($pointsWeightedChange) {
            $updateReason = 'weighted_points_outdated';
        }
        if ($achievementSetVersionChanged) {
            $updateReason = 'version_mismatch';
        }
        if ($updateReason) {
            $game->playerGames()
                ->where(function ($query) use ($game, $updateReason) {
                    if ($updateReason === 'weighted_points_outdated') {
                        $query->whereNot('points_weighted_total', '=', $game->TotalTruePoints)
                            ->orWhereNull('points_weighted_total');
                    }
                    if ($updateReason === 'version_mismatch') {
                        $query->whereNot('achievement_set_version_hash', '=', $game->achievement_set_version_hash)
                            ->orWhereNull('achievement_set_version_hash');
                    }
                })
                ->update([
                    'update_status' => $updateReason,
                    'achievements_total' => $game->achievements_published,
                    'points_total' => $game->points_total,
                    'points_weighted_total' => $game->TotalTruePoints,
                ]);
        }
    }
}

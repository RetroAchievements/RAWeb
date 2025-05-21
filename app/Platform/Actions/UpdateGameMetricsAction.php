<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\PlayerAchievementSet;
use App\Models\PlayerGame;
use App\Platform\Events\GameMetricsUpdated;
use App\Platform\Jobs\UpdateGamePlayerGamesJob;
use Illuminate\Database\Eloquent\Builder;
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

        $parentGame = getParentGameFromGameTitle($game->Title, $game->system_id);
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
                ->join('UserAccounts as user', 'user.ID', '=', 'player_games.user_id')
                ->where('player_games.achievements_unlocked', '>', 0)
                ->where('user.Untracked', false)
                ->count();
            $game->players_hardcore = $game->playerGames()
                ->join('UserAccounts as user', 'user.ID', '=', 'player_games.user_id')
                ->where('player_games.achievements_unlocked_hardcore', '>', 0)
                ->where('user.Untracked', false)
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

        // get median time to beat
        $query = PlayerGame::where('game_id', $game->id)
            ->whereNotNull('time_to_beat')->whereNull('time_to_beat_hardcore');
        [$game->times_beaten, $game->median_time_to_beat] = $this->getMedian($query, 'time_to_beat');

        $query = PlayerGame::where('game_id', $game->id)
            ->whereNotNull('time_to_beat_hardcore');
        [$game->times_beaten_hardcore, $game->median_time_to_beat_hardcore] =
             $this->getMedian($query, 'time_to_beat_hardcore');

        $game->saveQuietly();

        // get median time to complete for each associated set
        $gameAchievementSets = GameAchievementSet::where('game_id', $game->id)
            ->with('achievementSet')
            ->get();

        foreach ($gameAchievementSets as $gameAchievementSet) {
            $achievementSet = $gameAchievementSet->achievementSet;

            // NOTE: this only finds masters of the current set. it ignores users who may
            //       have previously mastered it before a revision
            $query = PlayerAchievementSet::where('achievement_set_id', $achievementSet->id)
                ->where('achievements_unlocked', '=', $achievementSet->achievements_published)
                ->where('achievements_unlocked_hardcore', '!=', $achievementSet->achievements_published);
            [$achievementSet->times_completed, $achievementSet->median_time_to_complete] =
                $this->getMedian($query, 'time_taken');

            $query = PlayerAchievementSet::where('achievement_set_id', $achievementSet->id)
                ->where('achievements_unlocked_hardcore', '=', $achievementSet->achievements_published);
            [$achievementSet->times_completed_hardcore, $achievementSet->median_time_to_complete_hardcore] =
                $this->getMedian($query, 'time_taken_hardcore');

            $achievementSet->save();
        }

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

    /**
     * @param Builder<PlayerGame>|Builder<PlayerAchievementSet> $query
     */
    private function getMedian(Builder $query, string $field): array
    {
        $count = $query->count();
        if ($count === 0) {
            return [0, null];
        }

        $query->select($field)->orderBy($field);

        if (($count % 2) == 1) {
            // odd. just get the middle item
            $query->offset((int) ($count / 2))->limit(1);
            $median = $query->value($field);
        } else {
            // even. get the two items in the middle and average them together
            $query->offset((int) ($count / 2) - 1)->limit(2);
            $values = $query->pluck($field)->toArray();
            $median = ($values[0] + $values[1]) / 2;
        }

        return [$count, $median];
    }
}

<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\PlayerAchievementSet;
use App\Models\PlayerGame;
use App\Models\System;
use App\Platform\Enums\PlayerStatRankingKind;
use App\Platform\Jobs\UpdateBeatenGamesLeaderboardJob;
use Illuminate\Database\Eloquent\Builder;

class UpdateGameBeatenMetricsAction
{
    public function execute(Game $game): void
    {
        // Get median time to beat (softcore only - has time_to_beat but not time_to_beat_hardcore).
        $query = PlayerGame::where('player_games.game_id', $game->id)
            ->leftJoin('unranked_users', 'player_games.user_id', '=', 'unranked_users.user_id')
            ->whereNull('unranked_users.id')
            ->whereNotNull('player_games.time_to_beat')
            ->whereNull('player_games.time_to_beat_hardcore');
        [$game->times_beaten, $game->median_time_to_beat] = $this->getMedian($query, 'player_games.time_to_beat');

        // Get median time to beat (hardcore).
        $query = PlayerGame::where('player_games.game_id', $game->id)
            ->leftJoin('unranked_users', 'player_games.user_id', '=', 'unranked_users.user_id')
            ->whereNull('unranked_users.id')
            ->whereNotNull('player_games.time_to_beat_hardcore');
        [$game->times_beaten_hardcore, $game->median_time_to_beat_hardcore] =
             $this->getMedian($query, 'player_games.time_to_beat_hardcore');

        $game->saveQuietly();

        // Get median time to complete for each associated set.
        $gameAchievementSets = GameAchievementSet::where('game_id', $game->id)
            ->with('achievementSet')
            ->get();

        foreach ($gameAchievementSets as $gameAchievementSet) {
            $achievementSet = $gameAchievementSet->achievementSet;

            // NOTE: This only finds masters of the current set.
            // It ignores users who may have previously mastered it before a revision.

            // Get median time to complete (softcore only - completed but NOT completed hardcore).
            $query = PlayerAchievementSet::where('player_achievement_sets.achievement_set_id', $achievementSet->id)
                ->leftJoin('unranked_users', 'player_achievement_sets.user_id', '=', 'unranked_users.user_id')
                ->whereNull('unranked_users.id')
                ->where('player_achievement_sets.achievements_unlocked', '=', $achievementSet->achievements_published)
                ->where('player_achievement_sets.achievements_unlocked_hardcore', '!=', $achievementSet->achievements_published);
            [$achievementSet->times_completed, $achievementSet->median_time_to_complete] =
                $this->getMedian($query, 'player_achievement_sets.time_taken');

            // Get median time to complete (hardcore).
            $query = PlayerAchievementSet::where('player_achievement_sets.achievement_set_id', $achievementSet->id)
                ->leftJoin('unranked_users', 'player_achievement_sets.user_id', '=', 'unranked_users.user_id')
                ->whereNull('unranked_users.id')
                ->where('player_achievement_sets.achievements_unlocked_hardcore', '=', $achievementSet->achievements_published);
            [$achievementSet->times_completed_hardcore, $achievementSet->median_time_to_complete_hardcore] =
                $this->getMedian($query, 'player_achievement_sets.time_taken_hardcore');

            $achievementSet->save();
        }

        $this->dispatchPlayerStatRankingJobs($game);
    }

    private function dispatchPlayerStatRankingJobs(Game $game): void
    {
        $systemId = $game->system_id;
        $specificKind = $this->determineRankingKindForGame($game, $systemId);

        // Update system-specific leaderboards.
        UpdateBeatenGamesLeaderboardJob::dispatch($systemId, PlayerStatRankingKind::AllBeaten)
            ->onQueue('game-beaten-metrics');

        if ($specificKind !== null) {
            UpdateBeatenGamesLeaderboardJob::dispatch($systemId, $specificKind)
                ->onQueue('game-beaten-metrics');
        }

        // Update overall leaderboards.
        UpdateBeatenGamesLeaderboardJob::dispatch(null, PlayerStatRankingKind::AllBeaten)
            ->onQueue('game-beaten-metrics');

        if ($specificKind !== null) {
            UpdateBeatenGamesLeaderboardJob::dispatch(null, $specificKind)
                ->onQueue('game-beaten-metrics');
        }
    }

    private function determineRankingKindForGame(Game $game, int $systemId): ?PlayerStatRankingKind
    {
        // Use get()->pluck() instead of pluck() directly because the JSON
        // arrow syntax doesn't work with pluck() through relationship queries.
        $tags = $game->tags()
            ->whereType('game')
            ->whereIn('name->en', ['Hack', 'Homebrew', 'Demo', 'Prototype', 'Unlicensed'])
            ->get()
            ->pluck('name');

        if ($tags->contains('Hack')) {
            return PlayerStatRankingKind::HacksBeaten;
        }

        if ($tags->contains('Homebrew') || System::isHomebrewSystem($systemId)) {
            return PlayerStatRankingKind::HomebrewBeaten;
        }

        // Demos and prototypes only contribute to "all", not a specific/filtered leaderboard.
        if ($tags->contains('Demo') || $tags->contains('Prototype')) {
            return null;
        }

        // Unlicensed games are bundled with retail in the RetailBeaten leaderboard.
        return PlayerStatRankingKind::RetailBeaten;
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

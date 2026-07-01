<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\PlayerAchievementSet;
use App\Models\PlayerGame;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class UpdateGameBeatenMetricsAction
{
    private const MEDIAN_FIELDS = [
        'player_games.time_to_beat',
        'player_games.time_to_beat_hardcore',
        'player_achievement_sets.time_taken',
        'player_achievement_sets.time_taken_hardcore',
    ];

    public function execute(Game $game): void
    {
        if (!$game->achievements_published) {
            $game->times_beaten = 0;
            $game->median_time_to_beat = null;
            $game->times_beaten_hardcore = 0;
            $game->median_time_to_beat_hardcore = null;
        } else {
            // Get median time to beat (casual only - has time_to_beat but not time_to_beat_hardcore).
            $query = PlayerGame::where('player_games.game_id', $game->id)
                ->leftJoin('unranked_users', 'player_games.user_id', '=', 'unranked_users.user_id')
                ->whereNull('unranked_users.id')
                ->whereNotNull('player_games.beaten_at')
                ->whereNull('player_games.beaten_hardcore_at');
            [$game->times_beaten, $game->median_time_to_beat] = $this->getMedian($query, 'player_games.time_to_beat');

            // Get median time to beat (hardcore).
            $query = PlayerGame::where('player_games.game_id', $game->id)
                ->leftJoin('unranked_users', 'player_games.user_id', '=', 'unranked_users.user_id')
                ->whereNull('unranked_users.id')
                ->whereNotNull('player_games.beaten_hardcore_at');
            [$game->times_beaten_hardcore, $game->median_time_to_beat_hardcore] =
                $this->getMedian($query, 'player_games.time_to_beat_hardcore');
        }

        $game->saveQuietly();

        // Get median time to complete for each associated set.
        $gameAchievementSets = GameAchievementSet::where('game_id', $game->id)
            ->with('achievementSet')
            ->get();

        foreach ($gameAchievementSets as $gameAchievementSet) {
            $achievementSet = $gameAchievementSet->achievementSet;

            if (!$achievementSet->achievements_published) {
                $achievementSet->times_completed = 0;
                $achievementSet->median_time_to_complete = null;
                $achievementSet->times_completed_hardcore = 0;
                $achievementSet->median_time_to_complete_hardcore = null;
            } else {
                // NOTE: This only finds masters of the current set.
                // It ignores users who may have previously mastered it before a revision.

                // Get median time to complete (casual only - completed but NOT completed hardcore).
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
            }

            $achievementSet->save();
        }
    }

    /**
     * @param Builder<PlayerGame>|Builder<PlayerAchievementSet> $query
     */
    private function getMedian(Builder $query, string $field): array
    {
        $field = $this->medianField($field);

        $rankedQuery = (clone $query)
            ->selectRaw($field . ' as median_value')
            ->selectRaw('ROW_NUMBER() OVER (ORDER BY ' . $field . ') as median_row')
            ->selectRaw('COUNT(*) OVER () as median_count');

        $result = DB::query()
            ->fromSub($rankedQuery->toBase(), 'ranked')
            ->whereRaw('median_row between FLOOR((median_count + 1) / 2.0) and FLOOR((median_count + 2) / 2.0)')
            ->selectRaw('MAX(median_count) as aggregate')
            ->selectRaw('AVG(median_value) as median')
            ->first();

        $count = (int) ($result->aggregate ?? 0);
        if ($count === 0) {
            return [0, null];
        }

        return [$count, $result->median !== null ? (float) $result->median : null];
    }

    private function medianField(string $field): string
    {
        if (!in_array($field, self::MEDIAN_FIELDS, true)) {
            throw new InvalidArgumentException("Unsupported median field [{$field}].");
        }

        return $field;
    }
}

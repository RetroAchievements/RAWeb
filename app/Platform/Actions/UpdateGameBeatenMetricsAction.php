<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\PlayerAchievementSet;
use App\Models\PlayerGame;
use App\Platform\Jobs\UpdateGameMetricsJob;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class UpdateGameBeatenMetricsAction
{
    public function execute(Game $game): void
    {
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

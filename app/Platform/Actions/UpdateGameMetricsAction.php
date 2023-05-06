<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Platform\Models\Game;

class UpdateGameMetricsAction
{
    public function execute(Game $game): void
    {
        // TODO

        // $game->achievements_total = $this->achievements()->count();

        /*
         * update published achievements metrics
         */
        // $publishedAchievements = $this->achievements()->published();
        // $this->attributes['achievements_published'] = $publishedAchievements->count();

        /*
         * fetch achievements data
         */
        // $publishedAchievements = $publishedAchievements->get(['trigger', 'points', 'points_ratio']);

        /*
         * update achievements version by changed hash
         */
        // if ($this->attributes['achievements_total']) {
        //     $this->attributes['achievements_version_hash'] = md5($publishedAchievements->implode('trigger'));
        //     if ($this->isDirty('achievements_version_hash')) {
        //         $this->attributes['achievements_version'] = $this->achievements_version + 1;
        //     }
        // }

        // $this->attributes['points_total'] = $publishedAchievements->sum('points');
        // $this->attributes['points_weighted'] = $publishedAchievements->sum('points_ratio');

        /*
         * update unpublished achievements metrics
         */
        // $this->attributes['achievements_unpublished'] = $this->achievements()->unpublished()->count();

        // $this->attributes['players_total'] = $this->players()->count();

        // $game->save();
    }
}

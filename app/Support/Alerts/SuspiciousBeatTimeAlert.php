<?php

declare(strict_types=1);

namespace App\Support\Alerts;

use App\Models\Game;
use App\Models\User;
use Carbon\CarbonInterval;

class SuspiciousBeatTimeAlert extends Alert
{
    public function __construct(
        public readonly User $user,
        public readonly Game $game,
        public readonly int $timeToBeatSeconds,
        public readonly int $medianTimeToBeatSeconds,
    ) {
    }

    /**
     * "[Scott](<https://retroachievements.org/user/Scott>) beat [Sonic the Hedgehog](<https://retroachievements.org/game/1>) in 2m 5s (3.3% of 1h median) - [Activity](<https://retroachievements.org/user/Scott/game/1/activity>)"
     */
    public function toDiscordMessage(): string
    {
        $playerTime = CarbonInterval::seconds($this->timeToBeatSeconds)->cascade()->forHumans(['short' => true]);
        $medianTime = CarbonInterval::seconds($this->medianTimeToBeatSeconds)->cascade()->forHumans(['short' => true]);
        $percentageOfMedian = ($this->timeToBeatSeconds / $this->medianTimeToBeatSeconds) * 100;

        $playerUrl = route('user.show', ['user' => $this->user]);
        $gameUrl = route('game.show', ['game' => $this->game]);
        $activityUrl = route('user.game.activity.show', ['user' => $this->user, 'game' => $this->game]);

        return sprintf(
            '[%s](<%s>) beat [%s](<%s>) in %s (%.1f%% of %s median) - [Activity](<%s>)',
            $this->user->display_name,
            $playerUrl,
            $this->game->title,
            $gameUrl,
            $playerTime,
            $percentageOfMedian,
            $medianTime,
            $activityUrl,
        );
    }
}
